import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { exec } from 'node:child_process';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const PORT = process.env.PORT || 3000;
const DATA_DIR = path.join(__dirname, 'data');
const STATE_FILE = path.join(DATA_DIR, 'state.json');
const MAX_BODY_BYTES = 25 * 1024 * 1024;

const MIME_TYPES = {
  '.html': 'text/html; charset=UTF-8',
  '.css': 'text/css; charset=UTF-8',
  '.js': 'application/javascript; charset=UTF-8',
  '.json': 'application/json; charset=UTF-8',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg',
  '.gif': 'image/gif',
  '.svg': 'image/svg+xml',
  '.ico': 'image/x-icon',
  '.pdf': 'application/pdf',
  '.woff': 'font/woff',
  '.woff2': 'font/woff2',
  '.ttf': 'font/ttf',
};

function sendJson(res, status, payload) {
  res.writeHead(status, {
    'Content-Type': 'application/json; charset=UTF-8',
    'Cache-Control': 'no-store',
  });
  res.end(JSON.stringify(payload));
}

function readBody(req, callback) {
  const chunks = [];
  let size = 0;
  let finished = false;

  function done(error, body) {
    if (finished) return;
    finished = true;
    callback(error, body);
  }

  req.on('data', (chunk) => {
    size += chunk.length;
    if (size > MAX_BODY_BYTES) {
      req.destroy();
      done(new Error('payload-too-large'));
      return;
    }
    chunks.push(chunk);
  });
  req.on('end', () => done(null, Buffer.concat(chunks).toString('utf8')));
  req.on('error', (error) => done(error));
}

function handleStateApi(req, res) {
  if (req.method === 'GET') {
    fs.readFile(STATE_FILE, 'utf8', (error, raw) => {
      if (error) {
        sendJson(res, 200, null);
        return;
      }
      try {
        sendJson(res, 200, JSON.parse(raw));
      } catch {
        sendJson(res, 200, null);
      }
    });
    return;
  }

  if (req.method === 'POST') {
    readBody(req, (error, body) => {
      if (error) {
        sendJson(res, 413, { error: 'State payload is too large.' });
        return;
      }
      let payload = null;
      try {
        payload = JSON.parse(body);
      } catch {
        sendJson(res, 400, { error: 'Invalid JSON payload.' });
        return;
      }
      if (!payload || typeof payload !== 'object' || !Array.isArray(payload.roster)) {
        sendJson(res, 400, { error: 'Invalid state payload.' });
        return;
      }
      fs.mkdir(DATA_DIR, { recursive: true }, (mkdirError) => {
        if (mkdirError) {
          sendJson(res, 500, { error: 'Unable to create data directory.' });
          return;
        }
        fs.writeFile(STATE_FILE, JSON.stringify(payload), 'utf8', (writeError) => {
          if (writeError) {
            sendJson(res, 500, { error: 'Unable to save state.' });
            return;
          }
          sendJson(res, 200, { ok: true });
        });
      });
    });
    return;
  }

  sendJson(res, 405, { error: 'Method not allowed.' });
}

const server = http.createServer((req, res) => {
  let reqUrl = req.url.split('?')[0];
  if (reqUrl === '/') {
    reqUrl = '/index.html';
  }

  if (reqUrl === '/api/state') {
    handleStateApi(req, res);
    return;
  }

  if (reqUrl === '/data' || reqUrl.startsWith('/data/')) {
    res.writeHead(404, { 'Content-Type': 'text/plain; charset=UTF-8' });
    res.end('404 Not Found');
    return;
  }

  const safePath = path.normalize(reqUrl).replace(/^(\.\.[/\\])+/, '');
  const filePath = path.join(__dirname, safePath);

  fs.stat(filePath, (err, stats) => {
    if (err || !stats.isFile()) {
      res.writeHead(404, { 'Content-Type': 'text/plain; charset=UTF-8' });
      res.end('404 Not Found');
      return;
    }

    const ext = path.extname(filePath).toLowerCase();
    const contentType = MIME_TYPES[ext] || 'application/octet-stream';
    const headers = { 'Content-Type': contentType };
    if (ext === '.html' || ext === '.css' || ext === '.js') {
      headers['Cache-Control'] = 'no-store';
    }

    res.writeHead(200, headers);
    const stream = fs.createReadStream(filePath);
    stream.pipe(res);
  });
});

server.listen(PORT, () => {
  const url = `http://localhost:${PORT}`;
  console.log(`\n======================================================`);
  console.log(` UCEN Faculty Profile Management Portal is running!`);
  console.log(` Local URL: ${url}`);
  console.log(`======================================================\n`);

  // Open default browser on Windows
  exec(`start ${url}`);
});
