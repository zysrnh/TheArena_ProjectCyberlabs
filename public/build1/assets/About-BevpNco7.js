import{u as f,r as l,j as e,H as y}from"./app-CecHNWxe.js";import{F as j}from"./Footer-CS3GdJ50.js";import{N as v,X as w}from"./Navigation-BoVsfGi0.js";/* empty css            */function L(){const{auth:N,aboutData:a,facilities:n,activeEventNotif:s=null}=f().props,[_,h]=l.useState(!1),[d,g]=l.useState(0),[k,m]=l.useState(!1),[u,x]=l.useState(!1);l.useEffect(()=>{s&&x(!0)},[s]),l.useEffect(()=>{console.log("Facilities data:",n)},[n]),l.useEffect(()=>{const t=()=>{const r=window.scrollY;h(r>50),r>d&&r>50?m(!0):(r<d||r<=50)&&m(!1),g(r)};return window.addEventListener("scroll",t),()=>window.removeEventListener("scroll",t)},[d]);const c=()=>{x(!1)},b=()=>{s?.whatsapp_url&&(window.open(s.whatsapp_url,"_blank","noopener,noreferrer"),c())},p=t=>t?t.startsWith("http")?t:`/storage/${t}`:"https://images.unsplash.com/photo-1504450874802-0ba2bcd9b5ae?w=1200",o=t=>{const r=t?.toLowerCase()||"";return r.includes("cafe")||r.includes("resto")?"https://images.unsplash.com/photo-1554118811-1e0d58224f24?w=800":r.includes("makanan")?"https://images.unsplash.com/photo-1566478989037-eec170784d0b?w=800":r.includes("minuman")?"https://images.unsplash.com/photo-1534353436294-0dbd4bdac845?w=800":r.includes("ganti")?"https://images.unsplash.com/photo-1534349762230-e0cadf78f5da?w=800":r.includes("parkir")?"https://images.unsplash.com/photo-1590674899484-d5640e854abe?w=800":r.includes("wifi")?"https://images.unsplash.com/photo-1551808525-51a94da548ce?w=800":r.includes("tribun")?"https://images.unsplash.com/photo-1546519638-68e109498ffc?w=800":"https://images.unsplash.com/photo-1504450874802-0ba2bcd9b5ae?w=800"},i=t=>t?e.jsx("div",{className:"prose prose-invert max-w-none prose-headings:text-white prose-p:text-gray-200 prose-strong:text-white prose-ul:text-gray-200 prose-ol:text-gray-200",dangerouslySetInnerHTML:{__html:t}}):null;return e.jsxs(e.Fragment,{children:[e.jsx(y,{title:"THE ARENA - About"}),e.jsx("style",{children:`
      @keyframes fade-in {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes modal-appear {
  from {
    opacity: 0;
    transform: translateY(20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.animate-fade-in {
  animation: fade-in 0.3s ease-out;
}

.animate-modal-appear {
  animation: modal-appear 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap');
        * {
          font-family: 'Montserrat', sans-serif;
        }
        
        .prose-invert h2, .prose-invert h3 {
          color: #fff;
          font-weight: 700;
        }
        .prose-invert p {
          color: #e5e7eb;
          word-wrap: break-word;
          overflow-wrap: break-word;
          hyphens: auto;
        }
        .prose-invert strong {
          color: #fff;
          font-weight: 600;
        }
        .prose-invert ul, .prose-invert ol {
          color: #e5e7eb;
        }
        .prose-invert a {
          color: #ffd22f;
          word-break: break-word;
        }
        .prose-invert a:hover {
          color: #ffc107;
        }
        
        /* Mobile text optimization */
        @media (max-width: 640px) {
          .prose-invert p {
            font-size: 14px;
            line-height: 1.6;
          }
          .prose-invert h2 {
            font-size: 20px;
          }
          .prose-invert h3 {
            font-size: 18px;
          }
        }
          @keyframes float {
  0%, 100% {
    transform: translateY(0px);
  }
  50% {
    transform: translateY(-10px);
  }
}

@keyframes pulse-ring {
  0% {
    transform: scale(1);
    opacity: 1;
  }
  100% {
    transform: scale(1.5);
    opacity: 0;
  }
}

.animate-float {
  animation: float 3s ease-in-out infinite;
}

.animate-pulse-ring {
  animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

      `}),e.jsxs("div",{className:"min-h-screen flex flex-col bg-white",children:[e.jsx(v,{activePage:"tentang"}),e.jsx("div",{className:"bg-[#013064] py-12 md:py-16 lg:py-20 px-4 md:px-8 lg:px-16",children:e.jsxs("div",{className:"max-w-7xl mx-auto",children:[e.jsx("p",{className:"text-[#ffd22f] text-lg md:text-xl lg:text-2xl font-semibold mb-3 md:mb-4",children:a?.hero?.subtitle||"Tentang"}),e.jsx("h1",{className:"text-white text-3xl md:text-4xl lg:text-5xl xl:text-6xl font-bold leading-tight",children:a?.hero?.title||"The Arena History"})]})}),e.jsx("div",{className:"flex-1",children:e.jsxs("div",{className:"grid md:grid-cols-2",children:[e.jsx("div",{className:"relative h-[350px] md:h-[400px] lg:h-[450px]",children:e.jsx("img",{src:p(a?.arena?.image_url),alt:a?.arena?.title||"The Arena Basketball Court",className:"w-full h-full object-cover"})}),e.jsxs("div",{className:"bg-[#003f84] text-white p-6 md:p-10 lg:p-14 flex flex-col justify-center",children:[e.jsx("h2",{className:"text-white text-2xl md:text-3xl lg:text-4xl font-bold mb-4 md:mb-6 leading-tight",children:a?.arena?.title||"The Arena"}),e.jsxs("div",{className:"space-y-3 md:space-y-4 text-gray-200 text-xs md:text-sm lg:text-base leading-relaxed",children:[a?.arena?.description_1&&i(a.arena.description_1),a?.arena?.description_2&&i(a.arena.description_2),a?.arena?.description_3&&i(a.arena.description_3)]})]})]})}),e.jsxs("div",{className:"grid md:grid-cols-2",children:[e.jsxs("div",{className:"bg-[#003f84] text-white p-6 md:p-10 lg:p-14 flex flex-col justify-center order-2 md:order-1",children:[e.jsx("h2",{className:"text-white text-2xl md:text-3xl lg:text-4xl font-bold mb-4 md:mb-6 leading-tight",children:"Komunitas & Klub Basket"}),e.jsxs("div",{className:"space-y-3 md:space-y-4 text-gray-200 text-xs md:text-sm lg:text-base leading-relaxed",children:[a?.komunitas?.description_1&&i(a.komunitas.description_1),a?.komunitas?.description_2&&i(a.komunitas.description_2),a?.komunitas?.description_3&&i(a.komunitas.description_3)]})]}),e.jsx("div",{className:"relative h-[350px] md:h-[400px] lg:h-[450px] order-1 md:order-2",children:e.jsx("img",{src:p(a?.komunitas?.image_url),alt:a?.komunitas?.title||"Basketball Community",className:"w-full h-full object-cover"})})]}),e.jsx("div",{className:"grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3",children:Array.isArray(n)&&n.length>0?n.filter(t=>t.name.toLowerCase()!=="tribun penonton").map(t=>e.jsxs("div",{className:"group cursor-pointer overflow-hidden relative h-[280px] md:h-[320px] lg:h-[350px]",children:[e.jsx("img",{src:t.image_url?t.image_url.startsWith("http")?t.image_url:t.image_url.startsWith("images/")?`/${t.image_url}`:`/storage/${t.image_url}`:o(t.name),alt:t.name,className:"w-full h-full object-cover group-hover:scale-110 transition-transform duration-500",onError:r=>{r.target.src=o(t.name)}}),e.jsx("div",{className:"absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent"}),e.jsxs("div",{className:"absolute bottom-0 left-0 right-0 p-4 md:p-6 text-white",children:[e.jsx("span",{className:"text-[#ffd22f] text-sm md:text-base font-semibold mb-1 md:mb-2 block",children:"Fasilitas"}),e.jsx("h3",{className:"text-xl md:text-2xl lg:text-3xl font-bold",children:t.name})]})]},t.id)):e.jsx("div",{className:"col-span-full bg-gray-50 py-16 px-4",children:e.jsxs("div",{className:"text-center max-w-md mx-auto",children:[e.jsx("div",{className:"w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4",children:e.jsx("svg",{className:"w-8 h-8 text-gray-400",fill:"none",stroke:"currentColor",viewBox:"0 0 24 24",children:e.jsx("path",{strokeLinecap:"round",strokeLinejoin:"round",strokeWidth:2,d:"M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"})})}),e.jsx("h3",{className:"text-lg font-semibold text-gray-700 mb-2",children:"Belum Ada Fasilitas"}),e.jsx("p",{className:"text-sm text-gray-500",children:"Data fasilitas akan ditampilkan di sini"})]})})}),e.jsx("div",{className:"grid grid-cols-1 md:grid-cols-3",children:(()=>{const t=n?.find(r=>r.name.toLowerCase().includes("tribun"))||a?.tribun;return e.jsxs(e.Fragment,{children:[e.jsxs("div",{className:"group cursor-pointer overflow-hidden relative h-[280px] md:h-[320px] lg:h-[350px]",children:[e.jsx("img",{src:t?.image_url?t.image_url.startsWith("http")?t.image_url:t.image_url.startsWith("images/")?`/${t.image_url}`:`/storage/${t.image_url}`:o("tribun penonton"),alt:t?.title||t?.name||"Tribun Penonton",className:"w-full h-full object-cover group-hover:scale-110 transition-transform duration-500",onError:r=>{r.target.src=o("tribun penonton")}}),e.jsx("div",{className:"absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent"}),e.jsxs("div",{className:"absolute bottom-0 left-0 right-0 p-4 md:p-6 text-white",children:[e.jsx("span",{className:"text-[#ffd22f] text-sm md:text-base font-semibold mb-1 md:mb-2 block",children:t?.subtitle||"Fasilitas"}),e.jsx("h3",{className:"text-xl md:text-2xl lg:text-3xl font-bold",children:t?.title||t?.name||"Tribun Penonton"})]})]}),e.jsx("div",{className:"md:col-span-2 bg-[#003f84] text-white p-4 md:p-6 lg:p-8 flex flex-col justify-center h-[280px] md:h-[320px] lg:h-[350px]",children:e.jsxs("div",{className:"space-y-3 md:space-y-4 text-gray-200 text-xs md:text-sm leading-relaxed",children:[a?.tribun?.description_1&&i(a.tribun.description_1),a?.tribun?.description_2&&i(a.tribun.description_2),a?.tribun?.description_3&&i(a.tribun.description_3)]})})]})})()}),e.jsx("div",{className:"bg-[#003f84] text-white py-8 md:py-12 lg:py-16 px-4 sm:px-6 md:px-8 lg:px-12 xl:px-20",children:e.jsx("div",{className:"max-w-7xl mx-auto",children:e.jsxs("div",{className:"space-y-3 sm:space-y-4 md:space-y-6 text-gray-200 text-sm sm:text-base md:text-lg leading-relaxed break-words",children:[a?.full_description?.description_1&&i(a.full_description.description_1),a?.full_description?.description_2&&i(a.full_description.description_2),a?.full_description?.description_3&&i(a.full_description.description_3)]})})}),u&&s&&e.jsxs("div",{className:"fixed inset-0 z-[60] flex items-center justify-center p-4 animate-fade-in",children:[e.jsx("div",{className:"absolute inset-0 bg-black/70 backdrop-blur-sm",onClick:c}),e.jsxs("div",{className:"relative bg-white rounded-xl max-w-sm w-full max-h-[85vh] overflow-y-auto shadow-2xl animate-modal-appear border-2 border-gray-800",children:[e.jsx("button",{onClick:c,className:"absolute top-4 right-4 z-10 w-8 h-8 flex items-center justify-center bg-gray-100 hover:bg-gray-200 rounded-full transition-all duration-200 hover:scale-110",children:e.jsx(w,{className:"w-5 h-5 text-gray-800",strokeWidth:3})}),e.jsxs("div",{className:"bg-white px-5 py-4 text-center border-b-2 border-gray-800 sticky top-0 z-20",children:[e.jsx("h2",{className:"text-base font-black text-gray-900 uppercase tracking-tight mb-1",children:s.title}),e.jsx("p",{className:"text-[10px] font-bold text-gray-700 uppercase tracking-wide leading-tight",children:"Amankan Slot Sebelum Kuota Habis"})]}),e.jsxs("div",{className:"px-5 py-3 text-center border-b-2 border-gray-800 bg-gray-50",children:[e.jsx("p",{className:"text-xs font-black text-gray-900 uppercase tracking-tight mb-1",children:s.formatted_date}),s.formatted_time&&e.jsxs("p",{className:"text-[10px] font-bold text-gray-700 tracking-wide",children:["Jam ",s.formatted_time]})]}),(s.monthly_price||s.weekly_price)&&e.jsxs(e.Fragment,{children:[e.jsxs("div",{className:"grid grid-cols-2 gap-3 p-4",children:[s.monthly_price&&e.jsxs("div",{className:"border-2 border-gray-800 rounded-lg p-3",children:[e.jsxs("p",{className:"text-[10px] font-black text-gray-800 uppercase tracking-widest mb-1.5 leading-tight",children:["Bulanan",e.jsx("br",{}),"(Lebih Hemat)"]}),s.monthly_discount_percent&&s.monthly_original_price&&e.jsxs("p",{className:"text-[9px] text-gray-600 line-through mb-1",children:["Diskon ",s.monthly_discount_percent,"%"]}),e.jsxs("p",{className:"text-2xl font-black text-gray-800 mb-1",children:["Rp",s.formatted_monthly_price]}),e.jsxs("div",{className:"space-y-0.5 text-[9px] text-gray-700 font-bold mb-2 pb-2 border-b-2 border-gray-200",children:[e.jsx("p",{children:s.monthly_frequency}),e.jsxs("p",{children:[" +",s.monthly_loyalty_points]}),s.monthly_note&&e.jsx("p",{children:s.monthly_note})]}),e.jsxs("p",{className:"text-[8px] font-black text-gray-800 uppercase tracking-tight text-center",children:[s.participant_count,"+ Peserta"]})]}),s.weekly_price&&e.jsxs("div",{className:"border-2 border-gray-800 rounded-lg p-3 bg-gray-50",children:[e.jsx("p",{className:"text-[10px] font-black text-gray-800 uppercase tracking-widest mb-2",children:"Mingguan"}),e.jsxs("p",{className:"text-2xl font-black text-gray-800 mb-1",children:["Rp",s.formatted_weekly_price]}),e.jsx("p",{className:"text-[9px] font-bold text-gray-700 mb-2",children:"1x pertemuan"}),e.jsxs("div",{className:"space-y-0.5 text-[9px] text-gray-700 font-bold",children:[e.jsxs("p",{children:["+",s.weekly_loyalty_points]}),e.jsx("p",{children:s.weekly_note})]})]})]}),e.jsxs("div",{className:"px-4 py-3 bg-gray-50 border-y-2 border-gray-800",children:[e.jsx("p",{className:"text-[10px] font-black text-gray-800 uppercase tracking-widest mb-2",children:"Termasuk"}),e.jsx("div",{className:"grid grid-cols-2 gap-x-3 gap-y-1.5 text-[9px] font-bold text-gray-800 mb-2",children:s.benefits_list&&s.benefits_list.map((t,r)=>e.jsx("div",{children:e.jsx("p",{children:t.label||t})},r))}),e.jsx("p",{className:"text-[9px] font-black text-gray-800 uppercase tracking-tight pt-2 border-t-2 border-gray-300 text-center leading-tight",children:s.level_tagline})]})]}),!s.monthly_price&&!s.weekly_price&&s.description&&e.jsx("div",{className:"p-4 border-b-2 border-gray-800",children:e.jsx("p",{className:"text-[9px] font-bold text-gray-800 leading-relaxed text-center uppercase tracking-wide",children:s.description})}),s.image_url&&e.jsx("div",{className:"relative h-32 overflow-hidden mx-4 my-3 rounded-lg border-2 border-gray-800",children:e.jsx("img",{src:s.image_url,alt:s.title,className:"w-full h-full object-cover",onError:t=>{t.target.style.display="none"}})}),s.location&&e.jsxs("div",{className:"px-4 py-3 text-center border-t-2 border-gray-800 bg-gray-50",children:[e.jsx("p",{className:"text-[10px] font-black text-gray-800 uppercase tracking-widest mb-1",children:"Lokasi"}),e.jsx("p",{className:"text-xs font-bold text-gray-800",children:s.location})]}),e.jsx("div",{className:"p-4 bg-white border-t-2 border-gray-800 sticky bottom-0 z-20",children:e.jsx("button",{onClick:b,className:"w-full bg-gray-800 text-white py-3 rounded-lg font-black text-xs hover:bg-gray-900 active:scale-95 transition-all duration-200 uppercase tracking-widest border-2 border-gray-800 hover:shadow-lg",children:"Daftar Sekarang"})})]})]}),e.jsx(j,{})]}),e.jsxs("a",{href:"https://wa.me/6281222977985",target:"_blank",rel:"noopener noreferrer",className:"fixed bottom-6 right-6 z-50 group","aria-label":"Chat WhatsApp",children:[e.jsx("div",{className:"absolute inset-0 bg-[#25D366] rounded-full animate-pulse-ring"}),e.jsx("div",{className:"relative bg-[#25D366] hover:bg-[#20BA5A] w-14 h-14 md:w-16 md:h-16 rounded-full flex items-center justify-center shadow-2xl transition-all duration-300 hover:scale-110 animate-float",children:e.jsx("img",{src:"/images/whatsapp-symbol-logo-svgrepo-com.svg",alt:"WhatsApp",className:"w-8 h-8 md:w-9 md:h-9"})}),e.jsx("div",{className:"absolute right-full mr-3 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none",children:e.jsxs("div",{className:"bg-gray-900 text-white px-3 py-2 rounded-lg text-sm font-medium whitespace-nowrap shadow-xl",children:["Chat dengan Kami",e.jsx("div",{className:"absolute right-0 top-1/2 -translate-y-1/2 translate-x-full",children:e.jsx("div",{className:"border-8 border-transparent border-l-gray-900"})})]})})]})]})}export{L as default};
