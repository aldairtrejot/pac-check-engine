import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'
import vm from 'node:vm'

const source = readFileSync(new URL('../../resources/js/app/constancias/table.vue', import.meta.url), 'utf8')
const script = source.match(/<script setup>([\s\S]*?)<\/script>/)[1].replace(/^import[^\r\n]*$/gm, '')

const observation = '<b>Revisar la instancia</b>\nObservación del registro.'
const person = {
  nombre: 'María Elena',
  apellido_paterno: 'López',
  apellido_materno: 'Pérez',
  curp: 'CURP-DE-PRUEBA',
}
const confirmation = {
  status: false,
  requires_confirmation: true,
  requires_instancia_otro_confirmation: true,
  persona: person,
  // El contenido anterior no debe aparecer aunque lo envíe un cliente o servidor anterior.
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

test('Cancelar la validación de identidad termina sin enviar una aceptación confirmada ni un rechazo', async () => {
  const flow = workflow([confirmation], [{ isConfirmed: false, isDismissed: true }])

  await flow.updateStatus('ACEPTAR')

  assert.deepEqual(flow.requests, [{ id_respuesta: 'CONSTANCIA-PRUEBA', accion: 'ACEPTAR' }])
  assert.equal(flow.alerts.length, 1)
  assert.deepEqual(flow.errors, [])
})

test('Aceptar y continuar confirma la identidad mostrando el nombre, la CURP y las instrucciones', async () => {
  const flow = workflow([confirmation, success], [{ isConfirmed: true }])

  await flow.updateStatus('ACEPTAR')

  assert.equal(flow.requests.length, 2)
  assert.equal(flow.requests[1].confirmar_instancia_otro, true)
  assert.equal(flow.requests[1].id_respuesta, 'CONSTANCIA-PRUEBA')
  assert.equal(flow.alerts[0].title, 'Favor de validar que la constancia pertenezca a:')
  assert.match(flow.alerts[0].html, /<strong>Nombre:<\/strong> María Elena López Pérez/)
  assert.match(flow.alerts[0].html, /<strong>CURP:<\/strong> CURP-DE-PRUEBA/)
  assert.ok(!flow.alerts[0].html.includes(observation))
  assert.equal(flow.alerts[0].text, undefined)
  const instructions = flow.alerts[0].html.replace(/<[^>]*>/g, '').replace(/\s+/g, ' ')
  assert.ok(instructions.includes('Si el nombre y la CURP coinciden tanto en la constancia como en la tarjeta de información, selecciona “Aceptar y continuar”. En caso contrario, selecciona “Cancelar” para detener la acción y revisar la información.'))
  assert.equal(flow.alerts[0].cancelButtonText, 'Cancelar')
  assert.equal(flow.alerts[0].confirmButtonText, 'Aceptar y continuar')
  assert.deepEqual(flow.errors, [])
})

test('La alerta muestra valores de identidad como texto y escapa etiquetas HTML', async () => {
  const flow = workflow([{
    ...confirmation,
    persona: { ...person, nombre: '<img src=x onerror=alert(1)>', curp: '<script>alert(1)</script>' },
  }], [{ isConfirmed: false }])

  await flow.updateStatus('ACEPTAR')

  assert.ok(!flow.alerts[0].html.includes('<img'))
  assert.ok(!flow.alerts[0].html.includes('<script>'))
  assert.ok(flow.alerts[0].html.includes('&lt;img src=x onerror=alert(1)&gt;'))
  assert.ok(flow.alerts[0].html.includes('&lt;script&gt;alert(1)&lt;/script&gt;'))
  assert.deepEqual(flow.errors, [])
})

test('El nombre completo omite apellidos ausentes y espacios en los extremos', async () => {
  const flow = workflow([{
    ...confirmation,
    persona: { ...person, nombre: ' María ', apellido_paterno: null, apellido_materno: ' Pérez ' },
  }], [{ isConfirmed: false }])

  await flow.updateStatus('ACEPTAR')

  assert.match(flow.alerts[0].html, /<strong>Nombre:<\/strong> María Pérez<br>/)
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
