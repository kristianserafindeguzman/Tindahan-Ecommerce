// Optional wire-level check: starts a temporary loopback Reverb instance, verifies
// private subscription and signed HTTP-to-WebSocket delivery, then stops it.
// No database, .env file, installed service or persistent server setting is changed.
import assert from 'node:assert/strict'
import { randomBytes } from 'node:crypto'
import { createConnection, createServer } from 'node:net'
import { spawn, spawnSync } from 'node:child_process'
import { fileURLToPath } from 'node:url'
import { once } from 'node:events'

const backend = fileURLToPath(new URL('../../backend/', import.meta.url))
const probe = createServer()
probe.listen(0, '127.0.0.1')
await once(probe, 'listening')
const port = probe.address().port
await new Promise(resolve => probe.close(resolve))
const server = spawn(
  'php',
  ['artisan', 'reverb:start', '--host=127.0.0.1', `--port=${port}`],
  { cwd: backend, windowsHide: true, stdio: ['ignore', 'pipe', 'pipe'] }
)
let socket
const timeout = setTimeout(() => {
  socket?.destroy()
  server.kill()
  console.error('Reverb smoke test timed out')
  process.exitCode = 1
}, 20000)
const php = code => {
  const result = spawnSync(
    'php',
    [
      '-r',
      `require 'vendor/autoload.php'; $app = require 'bootstrap/app.php'; $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); ${code}`
    ],
    { cwd: backend, encoding: 'utf8', windowsHide: true }
  )
  if (result.status !== 0) throw new Error(result.stderr || result.stdout)
  return result.stdout.trim()
}

try {
  await new Promise((resolve, reject) => {
    server.stdout.on('data', chunk => {
      if (chunk.toString().includes('Starting server')) resolve()
    })
    server.stderr.on('data', chunk => reject(new Error(chunk.toString())))
    server.on('error', reject)
    server.on('exit', code => {
      if (code) reject(new Error(`Reverb exited: ${code}`))
    })
  })
  socket = createConnection({ host: '127.0.0.1', port })
  await once(socket, 'connect')
  let buffer = Buffer.alloc(0)
  let upgraded = false
  const messages = []
  let waiter
  socket.on('data', data => {
    buffer = Buffer.concat([buffer, data])
    if (!upgraded) {
      const end = buffer.indexOf('\r\n\r\n')
      if (end < 0) return
      assert.match(
        buffer.subarray(0, end).toString(),
        /101 Switching Protocols/
      )
      buffer = buffer.subarray(end + 4)
      upgraded = true
    }
    while (buffer.length >= 2) {
      let length = buffer[1] & 127
      let offset = 2
      if (length === 126) {
        if (buffer.length < 4) return
        length = buffer.readUInt16BE(2)
        offset = 4
      }
      if (length === 127) {
        if (buffer.length < 10) return
        length = Number(buffer.readBigUInt64BE(2))
        offset = 10
      }
      if (buffer.length < offset + length) return
      const opcode = buffer[0] & 15
      const body = buffer.subarray(offset, offset + length)
      buffer = buffer.subarray(offset + length)
      if (opcode === 1) {
        messages.push(JSON.parse(body.toString()))
        waiter?.()
      }
    }
  })
  const next = async event => {
    while (!messages.some(message => message.event === event))
      await new Promise(resolve => {
        waiter = resolve
      })
    waiter = null
    return messages.splice(
      messages.findIndex(message => message.event === event),
      1
    )[0]
  }
  const send = value => {
    const data = Buffer.from(JSON.stringify(value))
    const mask = randomBytes(4)
    const header = Buffer.alloc(data.length < 126 ? 2 : 4)
    header[0] = 0x81
    header[1] = 0x80 | (data.length < 126 ? data.length : 126)
    if (data.length >= 126) header.writeUInt16BE(data.length, 2)
    const masked = Buffer.from(data.map((byte, i) => byte ^ mask[i % 4]))
    socket.write(Buffer.concat([header, mask, masked]))
  }
  socket.write(
    `GET /app/tindahan?protocol=7&client=js&version=8.6.0 HTTP/1.1\r\nHost: 127.0.0.1:${port}\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Key: ${randomBytes(16).toString('base64')}\r\nSec-WebSocket-Version: 13\r\nOrigin: http://localhost\r\n\r\n`
  )
  const established = JSON.parse(
    (await next('pusher:connection_established')).data
  )
  assert.match(established.socket_id, /^\d+\.\d+$/)
  const auth = JSON.parse(
    php(
      `$request = Illuminate\\Http\\Request::create('/', 'POST', ['socket_id' => '${established.socket_id}', 'channel_name' => 'private-users.42']); echo json_encode(Illuminate\\Support\\Facades\\Broadcast::connection('reverb')->validAuthenticationResponse($request, true));`
    )
  )
  send({
    event: 'pusher:subscribe',
    data: { channel: 'private-users.42', auth: auth.auth }
  })
  await next('pusher_internal:subscription_succeeded')
  const eventId = php(
    `config(['broadcasting.connections.reverb.options.port' => ${port}]); Illuminate\\Support\\Facades\\Broadcast::purge('reverb'); $event = new App\\Events\\RealtimeChanged([42], 'orders'); event($event); echo $event->eventId;`
  )
  const received = await next('realtime.changed')
  assert.deepEqual(JSON.parse(received.data), {
    event_id: eventId,
    topic: 'orders'
  })
  console.log(
    'PASS: Reverb handshake, private subscription and immediate JSON event delivery without a queue worker'
  )
} finally {
  clearTimeout(timeout)
  socket?.destroy()
  server.kill()
}
