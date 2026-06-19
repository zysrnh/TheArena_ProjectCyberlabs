import{j as e,H as n,L as l}from"./app-CkKVO2oX.js";import{N as o}from"./Navigation-BJYLbU-I.js";import{F as x}from"./Footer-CeHHhwDT.js";import{C as c}from"./Contact-BnGNh6tq.js";import{C as d}from"./chevron-left-B6fNTlvm.js";/* empty css            */import"./phone-CxPV-JcC.js";function N({auth:m,news:a,latestNews:r,popularNews:i}){return e.jsxs(e.Fragment,{children:[e.jsx(n,{title:`THE ARENA - ${a.title}`}),e.jsx("style",{children:`
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap');
        * {
          font-family: 'Montserrat', sans-serif;
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

      `}),e.jsxs("div",{className:"min-h-screen flex flex-col bg-[#013064]",children:[e.jsx(o,{activePage:"news"}),e.jsxs("main",{className:"flex-1",children:[e.jsxs("div",{className:"relative h-[400px] md:h-[500px] lg:h-[600px] overflow-hidden",children:[e.jsx("div",{className:"absolute inset-0 bg-cover bg-center",style:{backgroundImage:`url('${a.image}')`,filter:"brightness(0.4)"}}),e.jsx("div",{className:"relative z-10 h-full flex items-end",children:e.jsxs("div",{className:"max-w-7xl mx-auto px-4 md:px-6 lg:px-8 w-full pb-8 md:pb-12",children:[e.jsx(l,{href:"/berita",children:e.jsx("button",{className:"mb-6 flex items-center gap-2 text-white hover:text-[#ffd22f] transition",children:e.jsx("div",{className:"w-10 h-10 bg-white rounded-full flex items-center justify-center",children:e.jsx(d,{className:"w-5 h-5 text-[#013064]"})})})}),e.jsx("h1",{className:"text-2xl md:text-4xl lg:text-5xl font-bold text-white leading-tight max-w-4xl",children:a.title})]})})]}),e.jsx("div",{className:"bg-[#013064] py-12 md:py-16 px-4",children:e.jsx("div",{className:"max-w-7xl mx-auto",children:e.jsxs("div",{className:"grid lg:grid-cols-3 gap-8 lg:gap-12",children:[e.jsx("div",{className:"lg:col-span-2",children:e.jsxs("div",{className:"bg-[#002855] p-6 md:p-8 lg:p-10 rounded-lg",children:[e.jsx("p",{className:"text-gray-300 text-sm mb-6 italic",children:a.date}),e.jsx("div",{className:"prose prose-invert max-w-none",children:a.content.split(`

`).map((s,t)=>e.jsx("p",{className:"text-gray-300 text-sm md:text-base mb-4 leading-relaxed",children:s},t))})]})}),e.jsxs("div",{className:"lg:col-span-1",children:[e.jsxs("div",{className:"mb-12",children:[e.jsx("h3",{className:"text-white text-lg font-bold mb-6",children:"Berita Terbaru"}),e.jsx("div",{className:"space-y-8",children:r.map((s,t)=>e.jsx(l,{href:`/berita/${s.id}`,children:e.jsxs("div",{className:"group cursor-pointer",children:[t===0&&e.jsxs("div",{className:"relative w-full max-w-[280px] h-[158px] overflow-hidden mb-4",children:[e.jsx("img",{src:s.image,alt:s.title,className:"w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"}),e.jsx("span",{className:"absolute top-1.5 left-1.5 bg-[#e74c3c] text-white px-1.5 py-0.5 text-[9px] font-semibold",children:"News"})]}),e.jsxs("p",{className:"text-gray-400 text-[9px] mb-2",children:["News - ",s.date]}),e.jsx("h4",{className:"text-white text-xs font-bold leading-tight line-clamp-2 group-hover:text-[#ffd22f] transition mb-3",children:s.title}),e.jsx("p",{className:"text-gray-300 text-[10px] leading-relaxed line-clamp-2",children:s.excerpt})]})},s.id))})]}),e.jsxs("div",{children:[e.jsx("h3",{className:"text-white text-lg font-bold mb-6",children:"Berita Populer"}),e.jsx("div",{className:"space-y-8",children:i.map((s,t)=>e.jsx(l,{href:`/berita/${s.id}`,children:e.jsxs("div",{className:"group cursor-pointer",children:[t===0&&e.jsxs("div",{className:"relative w-full max-w-[280px] h-[158px] overflow-hidden mb-4",children:[e.jsx("img",{src:s.image,alt:s.title,className:"w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"}),e.jsx("span",{className:"absolute top-1.5 left-1.5 bg-[#e74c3c] text-white px-1.5 py-0.5 text-[9px] font-semibold",children:"News"})]}),e.jsxs("p",{className:"text-gray-400 text-[9px] mb-2",children:["News - ",s.date]}),e.jsx("h4",{className:"text-white text-xs font-bold leading-tight line-clamp-2 group-hover:text-[#ffd22f] transition mb-3",children:s.title}),e.jsx("p",{className:"text-gray-300 text-[10px] leading-relaxed line-clamp-2",children:s.excerpt})]})},s.id))})]})]})]})})})]}),e.jsx(c,{}),e.jsx(x,{}),e.jsxs("a",{href:"https://wa.me/6281222977985",target:"_blank",rel:"noopener noreferrer",className:"fixed bottom-6 right-6 z-50 group","aria-label":"Chat WhatsApp",children:[e.jsx("div",{className:"absolute inset-0 bg-[#25D366] rounded-full animate-pulse-ring"}),e.jsx("div",{className:"relative bg-[#25D366] hover:bg-[#20BA5A] w-14 h-14 md:w-16 md:h-16 rounded-full flex items-center justify-center shadow-2xl transition-all duration-300 hover:scale-110 animate-float",children:e.jsx("img",{src:"/images/whatsapp-symbol-logo-svgrepo-com.svg",alt:"WhatsApp",className:"w-8 h-8 md:w-9 md:h-9"})}),e.jsx("div",{className:"absolute right-full mr-3 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none",children:e.jsxs("div",{className:"bg-gray-900 text-white px-3 py-2 rounded-lg text-sm font-medium whitespace-nowrap shadow-xl",children:["Chat dengan Kami",e.jsx("div",{className:"absolute right-0 top-1/2 -translate-y-1/2 translate-x-full",children:e.jsx("div",{className:"border-8 border-transparent border-l-gray-900"})})]})})]})]})]})}export{N as default};
