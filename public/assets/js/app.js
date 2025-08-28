document.addEventListener("DOMContentLoaded", () => {
  const s = document.getElementById("start_date");
  const e = document.getElementById("end_date");
  const d = document.getElementById("days");
  async function calc() {
    if (s && e && d && s.value && e.value) {
      const res = await fetch(
        `api/calc_days.php?start=${s.value}&end=${e.value}`
      );
      const json = await res.json();
      d.value = json.days ?? 0;
    }
  }
  if (s && e) {
    s.addEventListener("change", calc);
    e.addEventListener("change", calc);
  }
});
