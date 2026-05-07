const API = (path) => `../api/${path}`;

async function apiGet(path) {
  const r = await fetch(API(path), { credentials: "include" });
  return r.json();
}

async function apiPost(path, data) {
  const r = await fetch(API(path), {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    credentials: "include",
    body: new URLSearchParams(data || {})
  });
  return r.json();
}

function $(id){ return document.getElementById(id); }
function esc(s){ return String(s ?? "").replace(/[&<>"']/g, c=>({ "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;" }[c])); }

function setToast(el, msg, type="info"){
  if(!el) return;
  el.innerHTML = msg ? `<div class="toast">${esc(msg)}</div>` : "";
}

async function requireAuth(redirectToLogin=true){
  const me = await apiGet("auth_me.php");
  if(!me.ok) return null;
  if(!me.user && redirectToLogin){
    location.href = "login.html";
    return null;
  }
  return me.user;
}

// PAGE: login
async function pageLogin(){
  const user = await requireAuth(false);
  if(user) location.href = "index.html";

  $("btnLogin").onclick = async () => {
    setToast($("toast"), "");
    const email = $("email").value.trim();
    const password = $("password").value;
    const res = await apiPost("auth_login.php", { email, password });
    if(!res.ok) return setToast($("toast"), res.error || "Eroare", "bad");
    location.href = "index.html";
  };
}

// PAGE: index (cărți)
async function pageIndex(){
  const user = await requireAuth(true);
  $("me").textContent = `${user.name || "User"} • ${user.role}`;
  if(user.role === "librarian"){
    $("adminLink").style.display = "inline-block";
    $("btnAdminToggle").style.display = "inline-block";
  }

  $("btnLogout").onclick = async ()=> {
    await apiPost("auth_logout.php", {});
    location.href = "login.html";
  };

  $("btnGoLoans").onclick = ()=> location.href = "dashboard.html";

  if(user.role === "librarian"){
    const btnAdminToggle = $("btnAdminToggle");
    const btnAdminSave = $("btnAdminSave");
    const btnAdminReset = $("btnAdminReset");
    
    if(btnAdminToggle) {
      btnAdminToggle.onclick = ()=> {
        const adminCard = $("adminCardSection");
        if(adminCard) {
          adminCard.style.display = adminCard.style.display === "none" ? "block" : "none";
        }
      };
    }

    if(btnAdminSave) {
      btnAdminSave.onclick = async ()=>{
        setToast($("adminToast"), "");
        const payload = {
          title: $("adminTitle").value.trim(),
          author: $("adminAuthor").value.trim(),
          isbn: $("adminIsbn").value.trim(),
          category: $("adminCategory").value.trim(),
          year: $("adminYear").value.trim(),
          total_copies: $("adminTotal").value.trim()
        };

        if(!payload.title || !payload.author) return setToast($("adminToast"), "Completează titlu + autor.");

        const r = await apiPost("books_create.php", payload);
        if(!r.ok) return setToast($("adminToast"), r.error || "Nu pot crea.");
        setToast($("adminToast"), "Creat ✅");
        if(btnAdminReset) btnAdminReset.click();
        await loadBooks(1);
      };
    }

    if(btnAdminReset) {
      btnAdminReset.onclick = ()=>{
        $("adminTitle").value = "";
        $("adminAuthor").value = "";
        $("adminIsbn").value = "";
        $("adminCategory").value = "";
        $("adminYear").value = "";
        $("adminTotal").value = 1;
      };
    }
  }

  let currentPage = 1;

  async function loadBooks(page=1){
    currentPage = page;
    const q = $("q").value.trim();
    const onlyAvail = $("onlyAvail").checked ? "1" : "0";
    const res = await apiGet(`books_list.php?q=${encodeURIComponent(q)}&onlyAvail=${onlyAvail}&page=${page}`);
    if(!res.ok) return setToast($("toast"), "Nu pot încărca cărțile.");

    const items = res.items || [];
    $("booksBody").innerHTML = items.map(b=>{
      const avail = Number(b.available_copies);
      const total = Number(b.total_copies);
      const pill = avail>0 ? `<span class="pill ok">Disponibil: ${avail}/${total}</span>` : `<span class="pill bad">Indisponibil</span>`;
      return `
        <tr>
          <td><b>${esc(b.title)}</b><div class="small muted">${esc(b.author)}</div></td>
          <td>${esc(b.category || "-")}</td>
          <td>${esc(b.isbn || "-")}</td>
          <td>${esc(b.year || "-")}</td>
          <td>${pill}</td>
          <td>
            <button class="btn ok" ${avail>0 ? "" : "disabled"} data-borrow="${b.id}">
              Reservă
            </button>
          </td>
        </tr>
      `;
    }).join("");

    // paginare
    $("pager").innerHTML = `
      <div class="row">
        <button class="btn" ${res.page<=1?"disabled":""} id="prevPage">←</button>
        <span class="muted">Pagina <b>${res.page}</b> / ${res.pages}</span>
        <button class="btn" ${res.page>=res.pages?"disabled":""} id="nextPage">→</button>
        <span class="muted">Total: ${res.total}</span>
      </div>
    `;
    const prev = $("prevPage"), next = $("nextPage");
    if(prev) prev.onclick = ()=> loadBooks(Math.max(1, currentPage-1));
    if(next) next.onclick = ()=> loadBooks(currentPage+1);

    document.querySelectorAll("[data-borrow]").forEach(btn=>{
      btn.onclick = async ()=>{
        setToast($("toast"), "");
        const book_id = Number(btn.getAttribute("data-borrow"));
        const r = await apiPost("loan_borrow.php", { book_id });
        if(!r.ok) return setToast($("toast"), r.error || "Nu pot împrumuta.");
        setToast($("toast"), `Împrumut reușit! Scadent: ${r.due_date}`);
        await loadBooks(currentPage);
      };
    });
  }

  $("btnSearch").onclick = ()=> loadBooks(1);
  $("q").addEventListener("keydown", (e)=> { if(e.key==="Enter") loadBooks(1); });
  $("onlyAvail").onchange = ()=> loadBooks(1);

  await loadBooks(1);
}

// PAGE: dashboard (împrumuturile mele)
async function pageDashboard(){
  const user = await requireAuth(true);
  $("me").textContent = `${user.name || "User"} • ${user.role}`;

  $("btnLogout").onclick = async ()=> {
    await apiPost("auth_logout.php", {});
    location.href = "login.html";
  };
  $("btnBack").onclick = ()=> location.href = "index.html";

  async function loadMyLoans(){
    const res = await apiGet("loans_my.php");
    if(!res.ok) return setToast($("toast"), "Nu pot încărca împrumuturile.");

    const now = new Date();
    $("loansBody").innerHTML = (res.items || []).map(l=>{
      const status = l.status;
      const due = new Date(l.due_date);
      const isLate = status==="borrowed" && now > due;
      const pill = status==="borrowed"
        ? (isLate ? `<span class="pill warn">Întârziat</span>` : `<span class="pill ok">Împrumutat</span>`)
        : `<span class="pill bad">Returnat</span>`;
      return `
        <tr>
          <td><b>${esc(l.title)}</b><div class="small muted">${esc(l.author)}</div></td>
          <td>${esc(l.loan_date)}</td>
          <td>${esc(l.due_date)} ${isLate ? "⚠️" : ""}</td>
          <td>${esc(l.return_date || "-")}</td>
          <td>${pill}</td>
          <td>
            ${status==="borrowed"
              ? `<button class="btn primary" data-return="${l.id}">Returnează</button>`
              : `<span class="muted small">—</span>`
            }
          </td>
        </tr>
      `;
    }).join("");

    document.querySelectorAll("[data-return]").forEach(btn=>{
      btn.onclick = async ()=>{
        setToast($("toast"), "");
        const loan_id = Number(btn.getAttribute("data-return"));
        const r = await apiPost("loan_return.php", { loan_id });
        if(!r.ok) return setToast($("toast"), r.error || "Nu pot returna.");
        setToast($("toast"), "Returnare reușită ✅");
        await loadMyLoans();
      };
    });
  }

  await loadMyLoans();
}

// PAGE: admin
async function pageAdmin(){
  const user = await requireAuth(true);
  if(user.role !== "librarian"){
    location.href = "index.html";
    return;
  }
  $("me").textContent = `${user.name || "Admin"} • ${user.role}`;

  $("btnLogout").onclick = async ()=> {
    await apiPost("auth_logout.php", {});
    location.href = "login.html";
  };
  $("btnBack").onclick = ()=> location.href = "index.html";

  $("btnAdminToggle").onclick = ()=> {
    const panel = $("adminPanel");
    panel.style.display = panel.style.display === "none" ? "block" : "none";
  };

  $("btnScrollBooks").onclick = ()=> {
    document.querySelector("[id='formTitle']").scrollIntoView({ behavior: "smooth", block: "start" });
    $("adminPanel").style.display = "none";
  };

  $("btnScrollLoans").onclick = ()=> {
    const loansSection = document.querySelector("[id='adminLoans']").closest(".card");
    loansSection.scrollIntoView({ behavior: "smooth", block: "start" });
    $("adminPanel").style.display = "none";
  };

  async function reloadBooks(){
    const res = await apiGet("books_list.php?page=1");
    if(!res.ok) return setToast($("toast"), "Nu pot încărca cărțile.");
    const items = res.items || [];
    $("adminBooks").innerHTML = items.map(b=>`
      <tr>
        <td>${b.id}</td>
        <td><b>${esc(b.title)}</b><div class="small muted">${esc(b.author)}</div></td>
        <td>${esc(b.category||"-")}</td>
        <td>${esc(b.year||"-")}</td>
        <td>${esc(b.total_copies)} / <b>${esc(b.available_copies)}</b></td>
        <td class="row">
          <button class="btn" data-edit="${b.id}">Editează</button>
          <button class="btn danger" data-del="${b.id}">Șterge</button>
        </td>
      </tr>
    `).join("");

    document.querySelectorAll("[data-del]").forEach(btn=>{
      btn.onclick = async ()=>{
        setToast($("toast"), "");
        const id = Number(btn.getAttribute("data-del"));
        const r = await apiPost("books_delete.php", { id });
        if(!r.ok) return setToast($("toast"), r.error || "Nu pot șterge.");
        setToast($("toast"), "Șters ✅");
        await reloadBooks();
      };
    });

    document.querySelectorAll("[data-edit]").forEach(btn=>{
      btn.onclick = async ()=>{
        setToast($("toast"), "");
        const id = Number(btn.getAttribute("data-edit"));
        const b = items.find(x=>Number(x.id)===id);
        if(!b) return;
        $("bookId").value = b.id;
        $("title").value = b.title || "";
        $("author").value = b.author || "";
        $("isbn").value = b.isbn || "";
        $("category").value = b.category || "";
        $("year").value = b.year || "";
        $("total").value = b.total_copies || 1;
        $("formTitle").textContent = "Editează carte";
      };
    });
  }

  async function reloadLoans(){
    const res = await apiGet("loans_all.php");
    if(!res.ok) return;
    const now = new Date();
    $("adminLoans").innerHTML = (res.items||[]).map(l=>{
      const due = new Date(l.due_date);
      const late = l.status==="borrowed" && now > due;
      const pill = l.status==="borrowed"
        ? (late ? `<span class="pill warn">Întârziat</span>` : `<span class="pill ok">Împrumutat</span>`)
        : `<span class="pill bad">Returnat</span>`;
      return `
        <tr>
          <td>${l.id}</td>
          <td><b>${esc(l.user_name)}</b><div class="small muted">${esc(l.email)}</div></td>
          <td><b>${esc(l.title)}</b><div class="small muted">${esc(l.author)}</div></td>
          <td>${esc(l.loan_date)}</td>
          <td>${esc(l.due_date)} ${late ? "⚠️" : ""}</td>
          <td>${esc(l.return_date || "-")}</td>
          <td>${pill}</td>
          <td>${l.status==="borrowed" ? `<button class="btn primary" data-force-return="${l.id}">Returnează</button>` : "—"}</td>
        </tr>
      `;
    }).join("");

    document.querySelectorAll("[data-force-return]").forEach(btn=>{
      btn.onclick = async ()=>{
        setToast($("toast"), "");
        const loan_id = Number(btn.getAttribute("data-force-return"));
        const r = await apiPost("loan_return.php", { loan_id });
        if(!r.ok) return setToast($("toast"), r.error || "Nu pot returna.");
        setToast($("toast"), "Returnat ✅");
        await reloadBooks();
        await reloadLoans();
      };
    });
  }

  $("btnReset").onclick = ()=>{
    $("bookId").value = "";
    $("title").value = "";
    $("author").value = "";
    $("isbn").value = "";
    $("category").value = "";
    $("year").value = "";
    $("total").value = 1;
    $("formTitle").textContent = "Adaugă carte";
  };

  $("btnSave").onclick = async ()=>{
    setToast($("toast"), "");
    const payload = {
      id: $("bookId").value ? Number($("bookId").value) : undefined,
      title: $("title").value.trim(),
      author: $("author").value.trim(),
      isbn: $("isbn").value.trim(),
      category: $("category").value.trim(),
      year: $("year").value.trim(),
      total_copies: $("total").value.trim()
    };

    if(!payload.title || !payload.author) return setToast($("toast"), "Completează titlu + autor.");

    if(payload.id){
      const r = await apiPost("books_update.php", payload);
      if(!r.ok) return setToast($("toast"), r.error || "Nu pot salva.");
      setToast($("toast"), "Actualizat ✅");
    } else {
      const r = await apiPost("books_create.php", payload);
      if(!r.ok) return setToast($("toast"), r.error || "Nu pot crea.");
      setToast($("toast"), "Creat ✅");
    }
    $("btnReset").click();
    await reloadBooks();
  };

  await reloadBooks();
  await reloadLoans();
}

// AUTO: detectăm pagina
document.addEventListener("DOMContentLoaded", async ()=>{
  const page = document.body.getAttribute("data-page");
  if(page==="login") return pageLogin();
  if(page==="index") return pageIndex();
  if(page==="dashboard") return pageDashboard();
  if(page==="admin") return pageAdmin();
});
