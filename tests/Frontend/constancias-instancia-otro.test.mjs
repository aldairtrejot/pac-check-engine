import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'
import vm from 'node:vm'

const source = readFileSync(new URL('../../resources/js/app/constancias/table.vue', import.meta.url), 'utf8')
const script = source.match(/<script setup>([\s\S]*?)<\/script>/)[1].replace(/^import[^\r\n]*$/gm, '')

const observation = '<b>Revisar la instancia</b>\nObservación del registro.'
const confirmation = {
  status: false,
  requires_confirmation: true,
  requires_instancia_otro_confirmation: true,
  instancia_otro: observation,
}
const duplicate = { status: false, requires_confirmation: true, duplicate_concluido: true }
const success = { status: true, message: 'Constancia procesada.' }

function workflow(responses, decisions = []) {
  const requests = []
  const alerts = []
  const errors = []
  const sandbox = {
    ref: value => ({ value }),
    computed: getter => ({ get value() { return getter() } }),
    onMounted: () => {},
    watch: () => {},
    document: { getElementById: () => null },
    setTimeout: callback => setTimeout(callback, 0),
    $: () => ({ modal: () => {} }),
    showSpinner: () => {},
    hideSpinner: () => {},
    notyf: { success: () => {}, error: message => errors.push(message) },
    Swal: {
      fire: async options => {
        alerts.push(options)
        assert.ok(decisions.length > 0, 'La alerta debe tener una decisión de prueba.')
        return decisions.shift()
      },
    },
    axios: {
      post: async (url, payload) => {
        if (url === '/constancias/estatus') {
          requests.push(JSON.parse(JSON.stringify(payload)))
          assert.ok(responses.length > 0, 'No debe repetirse una decisión inesperadamente.')
          return { data: responses.shift() }
        }
        if (url === '/constancias/table') return { data: { list: [], allRow: 0, row: 0 } }
        if (url === '/constancias/data') return { data: { status: true, data: {} } }
        throw new Error(`Ruta inesperada: ${url}`)
      },
    },
  }

  // Ejecuta las funciones reales del componente con los servicios de interfaz simulados.
  vm.runInNewContext(`${script}\nglobalThis.subject = { updateStatus, selectedId };`, sandbox)
  sandbox.subject.selectedId.value = 'CONSTANCIA-PRUEBA'

  return { ...sandbox.subject, requests, alerts, errors }
}

test('Cancelar la observación termina sin enviar una aceptación confirmada ni un rechazo', async () => {
  const flow = workflow([confirmation], [{ isConfirmed: false, isDismissed: true }])

  await flow.updateStatus('ACEPTAR')

  assert.deepEqual(flow.requests, [{ id_respuesta: 'CONSTANCIA-PRUEBA', accion: 'ACEPTAR' }])
  assert.equal(flow.alerts.length, 1)
  assert.deepEqual(flow.errors, [])
})

test('Aceptar y continuar reenvía la decisión confirmada y muestra la observación como texto', async () => {
  const flow = workflow([confirmation, success], [{ isConfirmed: true }])

  await flow.updateStatus('ACEPTAR')

  assert.equal(flow.requests.length, 2)
  assert.equal(flow.requests[1].confirmar_instancia_otro, true)
  assert.equal(flow.requests[1].id_respuesta, 'CONSTANCIA-PRUEBA')
  assert.equal(flow.alerts[0].text, observation)
  assert.equal(flow.alerts[0].html, undefined)
  assert.equal(flow.alerts[0].cancelButtonText, 'Cancelar')
  assert.equal(flow.alerts[0].confirmButtonText, 'Aceptar y continuar')
  assert.deepEqual(flow.errors, [])
})

test('Sin observación solicitada por el servidor se acepta sin alerta adicional', async () => {
  const flow = workflow([success])

  await flow.updateStatus('ACEPTAR')

  assert.equal(flow.requests.length, 1)
  assert.equal(flow.alerts.length, 0)
  assert.deepEqual(flow.errors, [])
})

test('El rechazo normal conserva su motivo y no muestra la alerta de instancia', async () => {
  const flow = workflow([success])

  await flow.updateStatus('RECHAZAR', 'Evidencia incorrecta.')

  assert.deepEqual(flow.requests, [{
    id_respuesta: 'CONSTANCIA-PRUEBA', accion: 'RECHAZAR', motivo: 'Evidencia incorrecta.',
  }])
  assert.equal(flow.alerts.length, 0)
  assert.deepEqual(flow.errors, [])
})

test('La confirmación de instancia se conserva al confirmar un duplicado', async () => {
  const flow = workflow([confirmation, duplicate, success], [
    { isConfirmed: true }, { isConfirmed: true },
  ])

  await flow.updateStatus('ACEPTAR')

  assert.equal(flow.alerts.length, 2)
  assert.equal(flow.requests.length, 3)
  assert.equal(flow.requests[2].confirmar_instancia_otro, true)
  assert.equal(flow.requests[2].confirmar_duplicado, true)
  assert.deepEqual(flow.errors, [])
})

test('El rechazo del duplicado conserva la confirmación y no reabre la alerta de instancia', async () => {
  const flow = workflow([confirmation, duplicate, success], [
    { isConfirmed: true }, { isConfirmed: false },
  ])

  await flow.updateStatus('ACEPTAR')

  assert.equal(flow.alerts.length, 2)
  assert.equal(flow.requests.length, 3)
  assert.equal(flow.requests[2].confirmar_instancia_otro, true)
  assert.equal(flow.requests[2].rechazar_duplicado, true)
  assert.deepEqual(flow.errors, [])
})
