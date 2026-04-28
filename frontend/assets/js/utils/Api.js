export const rexApiCall = async (endpoint, action, body, params = {}) => {
  let defaults = {
    mode: 'same-origin',
    credentials: 'same-origin',
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(body)
  }
  const response = await fetch(`?rex-api-call=${endpoint}&action=${action}`, {
    ...defaults,
    ...params
  })
  const json = await response.json()
  if (json.succeeded) {
    return json.message
  }
  throw new Error(json.message)
}
