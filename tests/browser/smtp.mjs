import { createServer } from 'node:net';
import { appendFileSync } from 'node:fs';

// Loopback-only SMTP sink. Never relays mail or contacts an external host.
export async function startTestSmtp(mailbox) {
  const server = createServer(socket => {
    socket.setEncoding('utf8');
    let buffer = ''; let data = null; let from = ''; let to = [];
    socket.write('220 synthetic-mailbox ESMTP\r\n');
    socket.on('data', chunk => {
      buffer += chunk;
      while (buffer.includes('\r\n')) {
        const end = buffer.indexOf('\r\n'); const line = buffer.slice(0, end); buffer = buffer.slice(end + 2);
        if (data !== null) {
          if (line === '.') {
            appendFileSync(mailbox, JSON.stringify({ from, to, body: data.join('\r\n'), accepted: Date.now() }) + '\n');
            data = null; from = ''; to = []; socket.write('250 Message accepted by synthetic mailbox\r\n');
          } else data.push(line.startsWith('..') ? line.slice(1) : line);
          continue;
        }
        const command = line.split(' ')[0].toUpperCase();
        if (command === 'EHLO') socket.write('250-synthetic-mailbox\r\n250 8BITMIME\r\n');
        else if (command === 'HELO' || command === 'NOOP') socket.write('250 OK\r\n');
        else if (command === 'MAIL') { from = line.match(/<([^>]+)>/)?.[1] ?? ''; to = []; socket.write('250 OK\r\n'); }
        else if (command === 'RCPT') { to.push(line.match(/<([^>]+)>/)?.[1] ?? ''); socket.write('250 OK\r\n'); }
        else if (command === 'DATA') { data = []; socket.write('354 End with a single dot\r\n'); }
        else if (command === 'RSET') { from = ''; to = []; data = null; socket.write('250 OK\r\n'); }
        else if (command === 'QUIT') { socket.end('221 Bye\r\n'); }
        else socket.write('502 Unsupported synthetic SMTP command\r\n');
      }
    });
    socket.on('error', () => {});
  });
  await new Promise((resolve, reject) => { server.once('error', reject); server.listen(0, '127.0.0.1', resolve); });
  return { server, port: server.address().port };
}
