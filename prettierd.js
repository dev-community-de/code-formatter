#!/usr/bin/env node
const net = require('net');
const argv = process.argv.slice(1);
const port = 9870;

if (argv.includes('-c')) {
  // client
  const stdin = process.stdin;
  const stdout = process.stdout;
  const stderr = process.stderr;
  const file = argv.indexOf('-f');
  if (file === -1 || !argv[file + 1]) {
    stderr.write("missing filename\n");
    process.exit(1);
    return;
  }
  const con = net.createConnection({ port }, () => {
    // send filename
    con.write(argv[file + 1]);
    con.write("\n");
    // send stdin to server
    stdin.pipe(con, { end: true });
    con.on('data', msg => {
      stdout.write(msg, 'utf8')
    });
  });
  con.on('error', err => {
    stderr.write(err.message);
    process.exit(1);
  });
  return;
}

// server
const prettier = require("prettier");
const options = { allowHalfOpen: true };
const server = net.createServer(options, con => {
  let fileName = '';
  let seenName = false;
  let fileData = '';
  con.on('data', msg => {
    const input = msg.toString('utf8');
    if (seenName) {
      fileData += input;
      return;
    }
    const lineFeed = input.indexOf("\n");
    if (lineFeed > -1) {
      const before = input.substr(0, lineFeed);
      const after = input.substr(lineFeed + 1);
      fileName += before;
      fileData += after;
      seenName = true;
      return;
    }
    fileName += input;
  });
  con.on('end', () => {
    const config = prettier.resolveConfig.sync(fileName);
    const result = prettier.format(fileData, config);
    con.write(result);
    con.destroy();
  })
});
server.on('error', err => {
  console.error(err.message);
});
process.on('SIGINT', () => {
  server.close();
  process.exit();
});
server.listen(port);
