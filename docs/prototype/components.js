/* Shared UI components for Medicine Donor Connect BD (static prototype).
   Pages include: <script src="https://cdn.tailwindcss.com"></script> then <script src="components.js"></script>
   Placeholders: <div data-header="find"></div>  <div data-footer></div>  <div data-cards="0-6"></div> */
tailwind.config = { theme: { extend: {
  colors: { brand: { 50:'#EAF5F3', 100:'#D3EAE6', 600:'#0F6B66', 700:'#0B5753', 900:'#0A2E2D' },
    ink:'#14262B', muted:'#4A5F64', line:'#D9E4E2', canvas:'#F6FAF9', warm:{ 100:'#FFF1DC', 700:'#8A5200' } },
  fontFamily: { sans: ['"Plus Jakarta Sans"','"Hind Siliguri"','system-ui','sans-serif'] } } } };
document.head.insertAdjacentHTML('beforeend',
 '<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">' +
 '<style>:focus-visible{outline:3px solid #F2A93B;outline-offset:2px}@media(prefers-reduced-motion:reduce){*{transition:none!important}}</style>');

const MDC = {};
MDC.nav = [['home','index.html','Home'],['find','browse.html','Find Medicine'],['request','request-create.html','Request Medicine'],
  ['donate','listing-create.html','Donate Medicine'],['how','how-it-works.html','How It Works'],['safety','safety.html','Safety']];

/* Status badge: icon + text, never colour alone */
MDC.status = {
  available:['Available','bg-green-100 text-green-800','✓'], requested:['Requested','bg-blue-100 text-blue-800','↗'],
  pending:['Verification Pending','bg-amber-100 text-amber-800','◔'], verified:['Verified','bg-brand-100 text-brand-700','✓'],
  reserved:['Reserved','bg-amber-100 text-amber-800','●'], completed:['Completed','bg-slate-200 text-slate-800','✔'],
  cancelled:['Cancelled','bg-slate-100 text-slate-600','✕'], reported:['Reported','bg-orange-100 text-orange-800','⚑'],
  blocked:['Blocked','bg-red-100 text-red-800','⊘']
};
MDC.badge = k => { const s = MDC.status[k]; return `<span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full ${s[1]}"><span aria-hidden="true">${s[2]}</span>${s[0]}</span>`; };
MDC.typeBadge = t => t === 'Donation'
  ? '<span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-brand-100 text-brand-700">Donation</span>'
  : '<span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-warm-100 text-warm-700">Affordable Access</span>';
MDC.avatar = (name, size = 'w-10 h-10') => `<span class="${size} shrink-0 grid place-items-center rounded-full bg-brand-100 text-brand-700 font-bold" aria-hidden="true">${name.split(' ').map(w => w[0]).slice(0,2).join('')}</span>`;

/* Mock data (dummy) */
MDC.data = [
 {id:1,name:'Napa 500',generic:'Paracetamol',strength:'500 mg',form:'Tablet',qty:'20 tablets',area:'Dhanmondi, Dhaka',type:'Donation',status:'available'},
 {id:2,name:'Seclo 20',generic:'Omeprazole',strength:'20 mg',form:'Capsule',qty:'14 capsules',area:'Sylhet Sadar, Sylhet',type:'Affordable Access',status:'reserved'},
 {id:3,name:'Ace Syrup',generic:'Paracetamol',strength:'120 mg/5 ml',form:'Syrup',qty:'1 bottle',area:'Kotwali, Chattogram',type:'Donation',status:'available'},
 {id:4,name:'Napa Extend',generic:'Paracetamol',strength:'665 mg',form:'Tablet',qty:'30 tablets',area:'Mirpur, Dhaka',type:'Donation',status:'available'},
 {id:5,name:'Fexo 120',generic:'Fexofenadine',strength:'120 mg',form:'Tablet',qty:'10 tablets',area:'Boalia, Rajshahi',type:'Affordable Access',status:'available'},
 {id:6,name:'Maxpro 20',generic:'Esomeprazole',strength:'20 mg',form:'Tablet',qty:'28 tablets',area:'Khulna Sadar, Khulna',type:'Donation',status:'requested'}
];
MDC.card = m => `<article class="rounded-xl border border-line bg-white p-5 flex flex-col">
  <div class="flex items-start justify-between gap-2"><h3 class="font-bold text-lg">${m.name}</h3>${MDC.badge(m.status)}</div>
  <p class="text-sm text-muted">${m.generic}</p><p class="text-sm text-muted">${m.strength} &middot; ${m.form}</p>
  <dl class="mt-3 text-sm grid grid-cols-[auto_1fr] gap-x-3 gap-y-1"><dt class="text-muted">Quantity</dt><dd>${m.qty}</dd><dt class="text-muted">Area</dt><dd><span aria-hidden="true">📍</span> ${m.area}</dd></dl>
  <div class="mt-3 flex flex-wrap items-center gap-2">${MDC.typeBadge(m.type)}<span class="text-xs font-semibold text-brand-700"><span aria-hidden="true">✓</span> Verified Listing</span></div>
  <a href="medicine-details.html?id=${m.id}" class="mt-4 text-center py-2.5 rounded-lg border-2 border-brand-600 text-brand-700 font-semibold hover:bg-brand-50">View Details<span class="sr-only"> for ${m.name}</span></a></article>`;

MDC.header = active => `<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:m-2 focus:bg-white focus:p-2 rounded">Skip to content</a>
<header class="sticky top-0 z-30 bg-white/95 backdrop-blur border-b border-line"><div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between gap-4">
 <a href="index.html" class="flex items-center gap-2 font-bold text-brand-700"><span class="grid place-items-center w-9 h-9 rounded-lg bg-brand-600 text-white" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></span><span class="leading-tight text-sm sm:text-base">Medicine Donor<br class="sm:hidden"> Connect BD</span></a>
 <nav aria-label="Main" class="hidden lg:flex items-center gap-6 text-sm font-medium">${MDC.nav.map(n => n[0] === active ? `<a href="${n[1]}" class="text-brand-700 border-b-2 border-brand-600 py-5" aria-current="page">${n[2]}</a>` : `<a href="${n[1]}" class="hover:text-brand-600">${n[2]}</a>`).join('')}</nav>
 <div class="hidden lg:flex gap-2"><a href="login.html" class="px-4 py-2 rounded-lg text-sm font-semibold text-brand-700 hover:bg-brand-50">Log in</a><a href="register.html" class="px-4 py-2 rounded-lg text-sm font-semibold bg-brand-600 text-white hover:bg-brand-700">Register</a></div>
 <button id="menuBtn" class="lg:hidden p-2 rounded-lg border border-line" aria-expanded="false" aria-controls="mobileNav" aria-label="Open menu"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button></div>
 <nav id="mobileNav" aria-label="Mobile" class="hidden lg:hidden border-t border-line bg-white px-4 py-3 font-medium">${MDC.nav.map(n => `<a href="${n[1]}" class="block py-3">${n[2]}</a>`).join('')}
 <div class="grid grid-cols-2 gap-2 pt-3"><a href="login.html" class="text-center py-3 rounded-lg border border-brand-600 text-brand-700 font-semibold">Log in</a><a href="register.html" class="text-center py-3 rounded-lg bg-brand-600 text-white font-semibold">Register</a></div></nav></header>`;

MDC.footer = () => `<footer class="bg-brand-900 text-brand-100 mt-16"><div class="max-w-6xl mx-auto px-4 py-10 grid sm:grid-cols-3 gap-8">
 <div><p class="font-bold text-white">Medicine Donor Connect BD</p><p class="mt-2 text-sm">A university capstone prototype. Not a licensed pharmacy or medicine marketplace.</p></div>
 <nav aria-label="Footer" class="grid grid-cols-2 gap-2 text-sm"><a href="about.html" class="hover:underline">About</a><a href="how-it-works.html" class="hover:underline">How It Works</a><a href="safety.html" class="hover:underline">Safety</a><a href="privacy.html" class="hover:underline">Privacy</a><a href="terms.html" class="hover:underline">Terms</a><a href="contact.html" class="hover:underline">Contact</a></nav>
 <div class="text-sm"><p class="text-white font-semibold">Follow us</p><p class="mt-2 flex gap-4"><a href="#" class="hover:underline">Facebook</a><a href="#" class="hover:underline">LinkedIn</a><a href="#" class="hover:underline">YouTube</a></p></div></div>
 <p class="border-t border-white/15 text-center text-xs py-4">&copy; 2026 Medicine Donor Connect BD. All rights reserved.</p></footer>`;

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-header]').forEach(e => e.outerHTML = MDC.header(e.dataset.header));
  document.querySelectorAll('[data-footer]').forEach(e => e.outerHTML = MDC.footer());
  document.querySelectorAll('[data-cards]').forEach(e => { const [a, b] = e.dataset.cards.split('-').map(Number); e.innerHTML = MDC.data.slice(a, b).map(MDC.card).join(''); });
  const b = document.getElementById('menuBtn'), m = document.getElementById('mobileNav');
  if (b) b.addEventListener('click', () => { const open = m.classList.toggle('hidden') === false; b.setAttribute('aria-expanded', open); b.setAttribute('aria-label', open ? 'Close menu' : 'Open menu'); });
});
