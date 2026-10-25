"""Deployment regression checks using disposable files and mocked Docker builds.
Run: python3 tests/deploy/test_deploy_vps.py (requires PHP, Bash and rsync).
Runtime preflight and symlink checks execute real PHP against the fixture.
"""
import json, os, pathlib, subprocess, tarfile, tempfile
script=pathlib.Path(__file__).resolve().parents[2]/'deploy/deploy-vps.sh'
with tempfile.TemporaryDirectory(prefix='itechorbit-deploy-',) as tmp:
    base=pathlib.Path(tmp)
    bins=base/'bin'; bins.mkdir()
    docker=bins/'docker'
    docker.write_text('''#!/usr/bin/env python3
import os,sys,json,pathlib,subprocess
args=sys.argv[1:]
with open(os.environ['CALL_LOG'],'a') as f: f.write(json.dumps(args)+'\\n')
if args[0]=='inspect':
    fmt=args[args.index('--format')+1]
    if '.State.Running' in fmt: print('false' if 'stopped-ssr' in os.environ['LIVE_SOURCE'] and args[-1]=='itechorbit-local-ssr-1' else 'true')
    elif '.Image' in fmt: print('sha256:mock-node' if args[-1]=='itechorbit-local-ssr-1' else 'sha256:mock-php')
    else: print('/wrong/path' if 'wrong-mount' in os.environ['LIVE_SOURCE'] else os.environ['LIVE_SOURCE'])
elif args[:2]==['image','inspect']:
    sys.exit(1 if args[-1]==os.environ.get('MISSING_IMAGE') else 0)
elif args[0]=='exec' and '-r' in args:
    code=args[args.index('-r')+1]
    if 'Runtime storage permissions' in code or 'Laravel public storage link verified' in code:
        if os.environ.get('FAIL_PREFLIGHT') and 'Runtime storage permissions' in code:
            sys.exit(1)
        sys.exit(subprocess.run(['php','-r',code],cwd=os.environ['LIVE_SOURCE']).returncode)
elif args[0]=='run':
    if os.environ.get('FAIL_BUILD'): sys.exit(2)
    path=pathlib.Path(args[args.index('--volume')+1].split(':')[0])
    if 'composer' in args:
        (path/'vendor').mkdir(exist_ok=True)
        (path/'vendor/autoload.php').write_text('new vendor')
    elif args[-3:] == ['npm','run','build']:
        for rel in ['public/build/manifest.json','public/build/assets/current.js','bootstrap/ssr/ssr.js']:
            p=path/rel; p.parent.mkdir(parents=True,exist_ok=True); p.write_text('built')
''')
    docker.chmod(0o755)
    for name,contents in {'flock':'#!/bin/sh\nexit 0\n','realpath':'#!/bin/sh\nfor arg; do :; done\nprintf "%s\\n" "$arg"\n'}.items():
        p=bins/name; p.write_text(contents); p.chmod(0o755)
    for case in ['success','success-symlinks','success-existing-link','success-stopped-ssr','build-failure','missing-php-image','missing-node-image','wrong-mount','permission-failure','conflicting-directory','wrong-link']:
        case_dir=base/case; case_dir.mkdir()
        live=case_dir/'source'; live.mkdir()
        target=live
        stage=pathlib.Path(str(target)+'.deploy'); (stage/'incoming').mkdir(parents=True)
        for rel,text in {'.env':'secret','public/uploads/image.jpg':'upload','storage/private.dat':'private','bootstrap/cache/config.php':'cached','public/hot':'localhost','obsolete.php':'old','index.php':'old code','public/build/assets/previous.js':'old chunk','public/build/manifest.json':'old manifest'}.items():
            p=live/rel; p.parent.mkdir(parents=True,exist_ok=True); p.write_text(text)
        for rel in ['storage/logs','storage/app/public','storage/framework/cache/data','storage/framework/sessions','storage/framework/views']:
            (live/rel).mkdir(parents=True,exist_ok=True)
        if case=='success-existing-link':
            (live/'public/storage').symlink_to('../storage/app/public',target_is_directory=True)
        if case=='conflicting-directory':
            (live/'public/storage').mkdir()
            (live/'public/storage/keep.txt').write_text('existing data')
        if case=='wrong-link':
            (live/'public/storage').symlink_to('../wrong-target',target_is_directory=True)
        if case=='success-symlinks':
            for rel in ['public/uploads','storage','bootstrap/cache']:
                previous=live/rel; target_dir=case_dir/rel.replace('/','-'); previous.rename(target_dir); previous.symlink_to(target_dir,target_is_directory=True)
        for rel in ['test-deploy/sentinel','test-deploy.deploy/sentinel']:
            p=live/rel;p.parent.mkdir(parents=True,exist_ok=True);p.write_text('keep test files')
        fixture=case_dir/'fixture'; fixture.mkdir()
        (fixture/'index.php').write_text('new code')
        (fixture/'.env').write_text('MUST NOT COPY')
        rid='a'*40+'-123-1'
        with tarfile.open(stage/'incoming'/f'{rid}.tar.gz','w:gz') as tf:
            for p in fixture.iterdir(): tf.add(p,arcname=p.name)
        log=case_dir/'calls.jsonl'
        env=dict(os.environ,PATH=str(bins)+':'+os.environ['PATH'],LIVE_SOURCE=str(live),CALL_LOG=str(log))
        if case=='permission-failure': env['FAIL_PREFLIGHT']='1'
        if case=='missing-php-image': env['MISSING_IMAGE']='sha256:mock-php'
        if case=='missing-node-image': env['MISSING_IMAGE']='sha256:mock-node'
        if 'build-failure' in case: env['FAIL_BUILD']='1'
        migrate='false'
        result=subprocess.run(['bash',str(script),str(target),rid,migrate],env=env,text=True,capture_output=True)
        calls=[json.loads(line) for line in log.read_text().splitlines()] if log.exists() else []
        assert (live/'.env').read_text()=='secret'
        assert (live/'public/uploads/image.jpg').read_text()=='upload'
        assert (live/'storage/private.dat').read_text()=='private'
        assert (live/'bootstrap/cache/config.php').read_text()=='cached' # Docker exec is mocked.
        assert not any('storage:link' in c for c in calls)
        for c in calls:
            if 'artisan' in c:
                assert c[1:3]==['--user','www-data'],c
        if case=='conflicting-directory':
            assert (live/'public/storage/keep.txt').read_text()=='existing data'
        if case=='wrong-link':
            assert os.readlink(live/'public/storage')=='../wrong-target'
        if case.startswith('success'):
            assert os.readlink(live/'public/storage')=='../storage/app/public'
            assert (live/'public/storage').resolve()==(live/'storage/app/public').resolve()
            assert result.returncode==0, result.stderr
            assert (live/'index.php').read_text()=='new code'
            assert not (live/'obsolete.php').exists()
            assert not (live/'public/hot').exists()
            assert (live/'public/build/assets/previous.js').read_text()=='old chunk'
            assert (live/'public/build/assets/current.js').read_text()=='built'
            assert (stage/'backups'/rid/'public/build/manifest.json').read_text()=='old manifest'
            assert (stage/'backups'/rid/'index.php').read_text()=='old code'
            assert not (stage/'backups'/rid/'.env').exists()
            assert any('up' in c for c in calls)
            assert any(c[-3:]==['npm','run','build'] for c in calls)
            assert any(c[-3:]==['npm','ci','--include=dev'] for c in calls)
            for rel in ['test-deploy/sentinel','test-deploy.deploy/sentinel']:
                assert (live/rel).read_text()=='keep test files'
            assert not (stage/'backups'/rid/'test-deploy').exists()
        else:
            assert result.returncode!=0, (result.stdout,result.stderr,calls)
            assert (live/'index.php').read_text()=='old code'
            assert not any('down' in c for c in calls)
        if case.startswith('missing-'):
            container='itechorbit-local-ssr-1' if case=='missing-node-image' else 'php-workspace-production-itechorbit-php-1'
            assert container in result.stderr and env['MISSING_IMAGE'] in result.stderr
            assert 'Reconcile the affected container' in result.stderr
            assert (stage/'incoming'/f'{rid}.tar.gz').is_file()
            assert not (stage/'releases').exists()
            assert not (stage/'backups').exists()
            assert not any(c[0]=='run' or c[0] in ['stop','restart'] or 'artisan' in c for c in calls)
        assert not any('migrate' in c for c in calls)
        assert not any(any('mariadb' in arg or 'redis' in arg for arg in c) for c in calls)
        print(f'{case}: passed')
