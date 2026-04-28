export const calcDistance = (a, b) => {
  const diffX = b.x - a.x,
    diffY = b.y - a.y

  return Math.sqrt(Math.pow(diffX, 2) + Math.pow(diffY, 2))
}

export const limitNumberWithinRange = (num, min, max) => {
  const MIN = min || 1
  const MAX = max || 20
  const parsed = parseInt(num)
  return Math.min(Math.max(parsed, MIN), MAX)
}

export const randomSign = () => {
  return Math.random() < 0.5 ? 1 : -1
}

export const clamp = (num, min, max) => Math.min(Math.max(num, min), max)
