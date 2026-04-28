const rules = {
  rules: [
    {
      name: 'filter',
      from: '/(projekte|team)/:filter?',
      to: '/(projekte|team)/:filter?',
      containers: ['#filter-projects'],
      scroll: false
    }
  ],
  debug: false
}
export { rules }
