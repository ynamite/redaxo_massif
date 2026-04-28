export const blendColors = (colorA, colorB, amount) => {
  var f = parseInt(colorA.slice(1), 16),
    t = parseInt(colorB.slice(1), 16),
    R1 = f >> 16,
    G1 = (f >> 8) & 0x00ff,
    B1 = f & 0x0000ff,
    R2 = t >> 16,
    G2 = (t >> 8) & 0x00ff,
    B2 = t & 0x0000ff
  return (
    '#' +
    (
      0x1000000 +
      (Math.round((R2 - R1) * amount) + R1) * 0x10000 +
      (Math.round((G2 - G1) * amount) + G1) * 0x100 +
      (Math.round((B2 - B1) * amount) + B1)
    )
      .toString(16)
      .slice(1)
  )
}
