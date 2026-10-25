# Mail and DNS operations

Example placeholders: IP addresses, private email addresses, SSH users, host paths and infrastructure names below are illustrative. Replace them with verified values from your private deployment configuration before running commands. These examples do not change production settings.

[Deployment](../DEPLOYMENT.md) · [Services](SERVICES.md) · [Queues](SCHEDULER.md) · [Troubleshooting](TROUBLESHOOTING.md)

## Mail flow and current scope

Verified 26 October 2026 (Asia/Dhaka):

```text
Laravel / default worker -> private smtp:587 -> recipient's mail server
Internet -> mail.itechorbit.com:25 -> inbound filter/SRS/ARC -> owner@example.com
```

Website sender: **iTechOrbit Support Team <support@itechorbit.com>**. Inbound recipient: **support@itechorbit.com** only. There is no support mailbox, catch-all, public IMAP or public submission service. The inbound and outbound servers are separate; neither is provided by Namecheap Private Email or an external SMTP delivery service.

A real forwarded test was accepted by Gmail, then classified into Spam. Gmail acceptance is a transport result, not an inbox-placement guarantee. See [Gmail filtering](#gmail-inbox-placement).

## Outgoing website settings

**VPS / root:** edit `/srv/example-project/src/.env` after a private backup. Keep each key once:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp
MAIL_PORT=587
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_EHLO_DOMAIN=mail.itechorbit.com
MAIL_FROM_ADDRESS=support@itechorbit.com
MAIL_FROM_NAME="iTechOrbit Support Team"
QUEUE_CONNECTION=database
```

No SMTP password is needed on this private, restricted connection. Do not expose this service publicly or use these settings from a remote mail client. The outbound server permits the project's private subnet and sender domain, signs with DKIM, and sends via IPv4. If its Docker subnet changes, review `POSTFIX_mynetworks` rather than trusting every private network.

After editing:

```sh
docker exec --user www-data example-production-app-php-1 php artisan config:cache
docker exec --user www-data example-production-app-php-1 php artisan queue:restart
```

Expected: configuration cache succeeds and supervised workers restart gracefully. Email jobs also need the correct queue consumer. SMTP configuration alone does not drain `mail-notices` or `ai-pro` jobs.

## DNS ownership and records

Namecheap hosts authoritative domain DNS; Contabo controls reverse DNS. Preserve website `@`/`www` records when changing mail records.

| Panel / section | Type / host | Expected value |
| --- | --- | --- |
| Namecheap → Advanced DNS → Host Records | A / `mail` | `192.0.2.10` |
| Namecheap → Advanced DNS → Mail Settings → Custom MX | MX / `@`, priority 10 | `mail.itechorbit.com` |
| Namecheap → Host Records | TXT / `@` | `v=spf1 ip4:192.0.2.10 -all` (current single sender) |
| Namecheap → Host Records | TXT / `mail._domainkey` | Generated public DKIM record, not a private key |
| Namecheap → Host Records | TXT / `_dmarc` | `v=DMARC1; p=none` (monitoring policy) |
| Contabo → Network Services → Reverse DNS Management | PTR / `192.0.2.10` | `mail.itechorbit.com` |

Add other authorized senders to the **same** SPF record if adopting another service. Do not create multiple SPF records. Raising DMARC enforcement requires reviewing all legitimate senders first. Keep the DKIM key and DNS public key synchronized across upgrades.

**Any machine with `dig`; read-only:**

```sh
dig +short A mail.itechorbit.com
dig +short MX itechorbit.com
dig +short TXT itechorbit.com
dig +short TXT mail._domainkey.itechorbit.com
dig +short TXT _dmarc.itechorbit.com
dig +short -x 192.0.2.10
```

Use `dig @dns1.registrar-servers.com ...` to compare authoritative records with cached resolver results. DNS changes are not complete merely because the panel saved them.

Retrieve the current **public** DKIM TXT content on **VPS / example-user**:

```sh
docker exec example-production-app-smtp-1 cat /etc/opendkim/keys/itechorbit.com.txt
```

For Namecheap's TXT value, join quoted strings without parentheses/quotes. Never copy `itechorbit.com.private` into DNS or chat.

## Inspect SMTP without sending mail

**VPS / example-user; any directory:**

```sh
docker inspect --format '{{.State.Status}} {{.State.Health.Status}}' example-production-app-smtp-1
docker exec example-production-app-smtp-1 postqueue -p
docker exec example-production-app-smtp-1 postconf myhostname mynetworks inet_protocols defer_transports
docker logs --since 10m --tail 80 example-production-app-smtp-1
```

Expected: healthy; `myhostname=mail.itechorbit.com`, IPv4 delivery and an empty `defer_transports` setting. A queue can contain retrying remote deliveries; inspect the reason. `defer_transports = smtp` means an intentional delivery hold.

Test the PHP-side connection without submitting a message:

```sh
docker exec example-production-app-php-1 php -r '
$s = @stream_socket_client("tcp://smtp:587", $code, $error, 10);
if (!$s) { fwrite(STDERR, "$error ($code)\n"); exit(1); }
echo fgets($s);
fwrite($s, "QUIT\r\n");
fclose($s);
'
```

Expected banner starts with `220 mail.itechorbit.com`. Repeat with the worker container if only queued sending fails.

## Operate support forwarding

Durable configuration: `/srv/example-project/.docker/compose.forwarding.yml` plus `.docker/compose.forwarding.public.yml`. Both files are required for the public service. They are independent of the application workspace stack and source deployment.

**VPS / root; configuration-changing operation:** back up `.docker/forwarding/` privately before changing aliases or secrets, then:

```sh
cd /srv/example-project
docker compose -f .docker/compose.forwarding.yml -f .docker/compose.forwarding.public.yml config --quiet
docker compose -f .docker/compose.forwarding.yml -f .docker/compose.forwarding.public.yml up -d --wait --wait-timeout 120
```

Expected: `example-forwarding-inbound-1` healthy; only host port `192.0.2.10:25` published. The alias is stored in `.docker/forwarding/config/postfix-virtual.cf`. Changing the Gmail destination forwards future messages to a different account; review that address carefully before applying it. Preserve explicit recipient lookup configuration in `postfix-main.cf`; alias-only SMTP defaults previously accepted unknown domain recipients until the lookup map was enabled.

**VPS / example-user; read-only:**

```sh
docker exec example-forwarding-inbound-1 supervisorctl status
docker exec example-forwarding-inbound-1 postqueue -p
docker exec example-forwarding-inbound-1 rspamadm configtest
docker logs --since 10m --tail 100 example-forwarding-inbound-1
```

Postfix, postsrsd, Rspamd, its Redis process and the certificate/config change detector should run. Dovecot/IMAP is intentionally stopped. Unknown root-domain recipients must receive a 5xx response; attempts to relay to unrelated domains must be rejected. Test recipient negotiation without `DATA` first after changing restrictions.

## Certificates and forwarding state

Caddy provisions and renews `mail.itechorbit.com`. The forwarding container mounts only that hostname's certificate directory read-only using manual TLS paths. Its change detector reloads mail TLS when certificate files change. Mount the directory, not individual files pinned to old inodes.

Current certificate directory:

```text
/var/lib/docker/volumes/example-production-gateway_certificates/_data/caddy/certificates/acme-v02.api.letsencrypt.org-directory/mail.itechorbit.com
```

This is a documented mount source, not a directory for manual database-style edits. If gateway regeneration changes the CA/provisioner, inspect the new certificate path and adjust the forwarding mount; matching hostname aliases preserve issuance but not necessarily a custom provisioner path. Never share the entire gateway key store with an unrelated service.

Private forwarding state lives under parent `.docker/forwarding/`: configuration/SRS secret, ARC key copy, queued mail, filter state and logs. Outgoing spool and DKIM keys use separate named volumes. Back up both before image upgrades. Recreating a container must not rotate its signing key or SRS secret unexpectedly.

**Workstation / read-only network test:**

```sh
openssl s_client -starttls smtp -connect mail.itechorbit.com:25 -servername mail.itechorbit.com -verify_hostname mail.itechorbit.com -verify_return_error </dev/null
```

Expected: valid hostname/chain and no verification error. If the workstation has an incomplete CA store, supply its trusted CA bundle; do not disable verification to make a test pass.

## Delivery tests and failures

Send one controlled message to `support@itechorbit.com` from a different external account, with a unique subject/time. In logs, find the matching queue ID and `orig_to=<support@itechorbit.com>`, `to=<owner@example.com>`, `status=sent` and remote `250` acceptance. Check the destination inbox and Spam separately. Do not retry repeatedly when the outcome is unconfirmed.

| Result | Interpretation / next action |
| --- | --- |
| `status=sent`, remote `250` | Recipient mail server accepted it; inspect Gmail inbox/Spam |
| `status=deferred` | Temporary failure; inspect DNS/connectivity/remote rejection and leave queue intact |
| `status=bounced` | Permanent rejection; diagnose recipient/authentication/reputation before another test |
| PHP cannot resolve `smtp` | Outgoing service missing or not on PHP/worker's private network |
| `defer_transports = smtp` | Delivery held; remove/clear hold only after its original reason is resolved |
| Web contact is saved, no SMTP log | Check failed jobs, queue consumer and application notification logic |

When clearing a hold, use explicit `POSTFIX_defer_transports: ""` in the parent SMTP service, validate Compose and recreate **only smtp** using [the application Compose helper](SERVICES.md#target-one-application-compose-service). Simply removing the environment key previously left the hold in persistent Postfix configuration. Recheck `postconf -h defer_transports` before sending.

To halt inbound forwarding during recovery: `docker stop example-forwarding-inbound-1`. This interrupts receiving; sending remains separate. To restart unchanged: `docker start example-forwarding-inbound-1`. Preserve queued mail and investigate its state before resuming. Do not delete queues as a generic fix.

## Gmail inbox placement

Gmail makes the placement decision. SPF/DKIM/SRS/ARC and PTR help authentication but do not guarantee inbox delivery. For a message placed in Spam, inspect **More → Show original** for SPF, DKIM, DMARC and ARC results before changing server authentication.

For this owner's mailbox, an intentional exception can be created in Gmail: mark the test **Not spam**, then **Settings → See all settings → Filters and Blocked Addresses → Create a new filter**; set **To** to `support@itechorbit.com`, choose **Never send it to Spam**, and optionally categorize as **Primary**. This also bypasses Gmail spam classification for unwanted matching messages; the VPS filter still applies. Bcc-only deliveries may need a different matching criterion after examining actual headers. Do not filter only the original test sender if the goal is all support mail.

Replying from the Gmail inbox normally uses the Gmail address. Gmail “Send mail as” is a separate feature; the private SMTP service is not a public authenticated relay for Gmail. Do not expose it to enable that feature.
