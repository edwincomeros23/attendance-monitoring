
setInterval(() => {
  const now = new Date();
  const options = { year: 'numeric', month: 'long', day: 'numeric' };
  const time = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  document.getElementById('dateTime').innerText = `${now.toLocaleDateString(undefined, options)} | ${time}`;
}, 1000);

// teachers page: client-side search filter
document.addEventListener('DOMContentLoaded', () => {
  const search = document.getElementById('search');
  if (!search) return;
  const table = document.getElementById('facultyTable');
  const tbody = table && table.tBodies[0];

  search.addEventListener('input', (e) => {
    const q = e.target.value.trim().toLowerCase();
    if (!tbody) return;
    for (let row of tbody.rows) {
      const text = Array.from(row.cells).map(c => c.textContent.toLowerCase()).join(' ');
      row.style.display = q === '' || text.includes(q) ? '' : 'none';
    }
  });
});

