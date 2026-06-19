import{r as c,a as h,j as e,H as g,L as y}from"./app-CecHNWxe.js";import{c as j,N as b,X as w}from"./Navigation-BoVsfGi0.js";import{C as N}from"./circle-check-big-CWIAw4vz.js";import{C as l}from"./circle-alert-B7D1VkgL.js";import{U as v}from"./user-q_2GVb19.js";import{M as k}from"./mail--K0WpUD8.js";import{P as C}from"./phone-DBVEkRDB.js";import{L as D,E as P,a as E}from"./lock-DFVBPWRJ.js";/* empty css            *//**
 * @license lucide-react v0.554.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const S=[["path",{d:"M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2",key:"1yyitq"}],["circle",{cx:"9",cy:"7",r:"4",key:"nufk8"}],["line",{x1:"19",x2:"19",y1:"8",y2:"14",key:"1bvyxn"}],["line",{x1:"22",x2:"16",y1:"11",y2:"11",key:"1shjgl"}]],Y=j("user-plus",S);function F(){const[m,f]=c.useState(!1),[t,r]=c.useState(null),{data:i,setData:n,post:x,processing:d,errors:s}=h({username:"",email:"",phone:"",password:""}),p=a=>{a.preventDefault(),x("/register",{onSuccess:()=>{r({type:"success",message:"Registrasi berhasil! Selamat datang, Anda akan dialihkan ke halaman profil..."}),setTimeout(()=>{window.location.href="/profile"},1500)},onError:o=>{const u=o.username||o.email||o.phone||o.password||"Terjadi kesalahan saat registrasi";r({type:"error",message:u}),setTimeout(()=>r(null),5e3)}})};return e.jsxs(e.Fragment,{children:[e.jsx(g,{title:"Register"}),e.jsx("style",{children:`
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap');
        * {
          font-family: 'Montserrat', sans-serif;
        }
        
        @keyframes slideDown {
          from {
            opacity: 0;
            transform: translateY(-20px);
          }
          to {
            opacity: 1;
            transform: translateY(0);
          }
        }
        
        @keyframes progress {
          from {
            width: 100%;
          }
          to {
            width: 0%;
          }
        }
        
        @keyframes fadeInUp {
          from {
            opacity: 0;
            transform: translateY(30px);
          }
          to {
            opacity: 1;
            transform: translateY(0);
          }
        }
        
        @keyframes scaleIn {
          from {
            opacity: 0;
            transform: scale(0.9);
          }
          to {
            opacity: 1;
            transform: scale(1);
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
        
        @keyframes pulse {
          0%, 100% {
            opacity: 1;
          }
          50% {
            opacity: 0.5;
          }
        }
        
        .animate-slide-down {
          animation: slideDown 0.3s ease-out;
        }
        
        .animate-progress {
          animation: progress 5s linear;
        }
        
        .animate-fade-in-up {
          animation: fadeInUp 0.6s ease-out;
        }
        
        .animate-scale-in {
          animation: scaleIn 0.5s ease-out;
        }
        
        .animate-float {
          animation: float 3s ease-in-out infinite;
        }
        
        .animate-pulse-slow {
          animation: pulse 2s ease-in-out infinite;
        }
        
        .stagger-1 {
          animation-delay: 0.1s;
          opacity: 0;
          animation-fill-mode: forwards;
        }
        
        .stagger-2 {
          animation-delay: 0.2s;
          opacity: 0;
          animation-fill-mode: forwards;
        }
        
        .stagger-3 {
          animation-delay: 0.3s;
          opacity: 0;
          animation-fill-mode: forwards;
        }
        
        .stagger-4 {
          animation-delay: 0.4s;
          opacity: 0;
          animation-fill-mode: forwards;
        }
        
        .stagger-5 {
          animation-delay: 0.5s;
          opacity: 0;
          animation-fill-mode: forwards;
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

      `}),e.jsxs("div",{className:"min-h-screen flex flex-col bg-[#013064] relative overflow-hidden",children:[e.jsxs("div",{className:"absolute inset-0 overflow-hidden pointer-events-none",children:[e.jsx("div",{className:"absolute top-10 right-10 w-32 h-32 bg-[#ffd22f]/10 rounded-full blur-3xl animate-float"}),e.jsx("div",{className:"absolute bottom-40 left-20 w-40 h-40 bg-[#ffd22f]/5 rounded-full blur-3xl animate-float",style:{animationDelay:"1.5s"}}),e.jsx("div",{className:"absolute top-1/2 right-1/4 w-36 h-36 bg-[#ffd22f]/10 rounded-full blur-3xl animate-float",style:{animationDelay:"0.5s"}})]}),e.jsx(b,{activePage:"register"}),t&&e.jsxs("div",{className:"fixed inset-0 z-50 flex items-start justify-center pt-20 px-4",children:[e.jsx("div",{className:"absolute inset-0 bg-[#013064]/80 backdrop-blur-sm",onClick:()=>r(null)}),e.jsx("div",{className:"relative bg-white max-w-md w-full animate-slide-down shadow-2xl",children:e.jsxs("div",{className:`border-t-4 ${t.type==="success"?"border-green-500":"border-red-500"}`,children:[e.jsxs("div",{className:"bg-[#013064] px-6 py-4 flex items-center justify-between",children:[e.jsxs("div",{className:"flex items-center gap-3",children:[t.type==="success"?e.jsx(N,{className:"w-6 h-6 text-green-400"}):e.jsx(l,{className:"w-6 h-6 text-red-400"}),e.jsx("h3",{className:"font-bold text-white text-lg",children:t.type==="success"?"Berhasil":"Perhatian"})]}),e.jsx("button",{onClick:()=>r(null),className:"text-white/70 hover:text-white transition",children:e.jsx(w,{className:"w-5 h-5"})})]}),e.jsx("div",{className:"p-6 bg-white",children:e.jsx("p",{className:"text-[#013064] text-base leading-relaxed",children:t.message})}),e.jsx("div",{className:"h-1 bg-gray-200 overflow-hidden",children:e.jsx("div",{className:`h-full ${t.type==="success"?"bg-green-500":"bg-red-500"} animate-progress`})})]})})]}),e.jsx("main",{className:"flex-1 flex items-center justify-center py-12 px-4 relative z-10",children:e.jsxs("div",{className:"w-full max-w-md relative",children:[e.jsx("h1",{className:"text-[#ffd22f] text-4xl font-bold text-center mb-8 animate-fade-in-up stagger-1",children:"Registrasi"}),e.jsxs("form",{onSubmit:p,className:"space-y-6",children:[e.jsxs("div",{className:"animate-fade-in-up stagger-2",children:[e.jsxs("label",{className:"block text-[#ffd22f] text-sm font-medium mb-2",children:["Username ",e.jsx("span",{className:"text-red-400",children:"*"})]}),e.jsxs("div",{className:"relative",children:[e.jsx(v,{className:"absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"}),e.jsx("input",{type:"text",placeholder:"Username",value:i.username,onChange:a=>n("username",a.target.value),required:!0,className:"w-full pl-12 pr-4 py-3 bg-white text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ffd22f] transition"})]}),s.username&&e.jsxs("p",{className:"text-red-400 text-xs mt-1 flex items-center gap-1",children:[e.jsx(l,{className:"w-3 h-3"}),s.username]})]}),e.jsxs("div",{className:"animate-fade-in-up stagger-3",children:[e.jsxs("label",{className:"block text-[#ffd22f] text-sm font-medium mb-2",children:["Email ",e.jsx("span",{className:"text-red-400",children:"*"})]}),e.jsxs("div",{className:"relative",children:[e.jsx(k,{className:"absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"}),e.jsx("input",{type:"email",placeholder:"Email",value:i.email,onChange:a=>n("email",a.target.value),required:!0,className:"w-full pl-12 pr-4 py-3 bg-white text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ffd22f] transition"})]}),s.email&&e.jsxs("p",{className:"text-red-400 text-xs mt-1 flex items-center gap-1",children:[e.jsx(l,{className:"w-3 h-3"}),s.email]})]}),e.jsxs("div",{className:"animate-fade-in-up stagger-3",children:[e.jsxs("label",{className:"block text-[#ffd22f] text-sm font-medium mb-2",children:["Nomor Telepon ",e.jsx("span",{className:"text-red-400",children:"*"})]}),e.jsxs("div",{className:"relative",children:[e.jsx(C,{className:"absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"}),e.jsx("input",{type:"tel",placeholder:"08123456789",value:i.phone,onChange:a=>n("phone",a.target.value),required:!0,className:"w-full pl-12 pr-4 py-3 bg-white text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ffd22f] transition"})]}),s.phone&&e.jsxs("p",{className:"text-red-400 text-xs mt-1 flex items-center gap-1",children:[e.jsx(l,{className:"w-3 h-3"}),s.phone]})]}),e.jsxs("div",{className:"animate-fade-in-up stagger-4",children:[e.jsxs("label",{className:"block text-[#ffd22f] text-sm font-medium mb-2",children:["Password ",e.jsx("span",{className:"text-red-400",children:"*"})]}),e.jsxs("div",{className:"relative",children:[e.jsx(D,{className:"absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"}),e.jsx("input",{type:m?"text":"password",placeholder:"Password",value:i.password,onChange:a=>n("password",a.target.value),required:!0,className:"w-full pl-12 pr-12 py-3 bg-white text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ffd22f] transition"}),e.jsx("button",{type:"button",onClick:()=>f(!m),className:"absolute right-4 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-800 transition",children:m?e.jsx(P,{className:"w-5 h-5"}):e.jsx(E,{className:"w-5 h-5"})})]}),s.password&&e.jsxs("p",{className:"text-red-400 text-xs mt-1 flex items-center gap-1",children:[e.jsx(l,{className:"w-3 h-3"}),s.password]})]}),e.jsx("button",{type:"submit",disabled:d,className:"w-full bg-[#ffd22f] text-[#013064] py-3 font-bold text-lg hover:bg-[#ffe066] transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 animate-fade-in-up stagger-5 shadow-lg hover:shadow-xl",children:d?e.jsxs(e.Fragment,{children:[e.jsx("div",{className:"w-5 h-5 border-2 border-[#013064] border-t-transparent rounded-full animate-spin"}),"Memproses..."]}):e.jsxs(e.Fragment,{children:[e.jsx(Y,{className:"w-5 h-5"}),"Daftar"]})}),e.jsxs("p",{className:"text-center text-white text-sm animate-fade-in-up stagger-5",children:["Sudah punya akun?"," ",e.jsx(y,{href:"/login",className:"text-[#ffd22f] hover:underline font-semibold",children:"Login di sini"})]})]})]})}),e.jsxs("a",{href:"https://wa.me/6281222977985",target:"_blank",rel:"noopener noreferrer",className:"fixed bottom-6 right-6 z-50 group","aria-label":"Chat WhatsApp",children:[e.jsx("div",{className:"absolute inset-0 bg-[#25D366] rounded-full animate-pulse-ring"}),e.jsx("div",{className:"relative bg-[#25D366] hover:bg-[#20BA5A] w-14 h-14 md:w-16 md:h-16 rounded-full flex items-center justify-center shadow-2xl transition-all duration-300 hover:scale-110 animate-float",children:e.jsx("img",{src:"/images/whatsapp-symbol-logo-svgrepo-com.svg",alt:"WhatsApp",className:"w-8 h-8 md:w-9 md:h-9"})}),e.jsx("div",{className:"absolute right-full mr-3 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none",children:e.jsxs("div",{className:"bg-gray-900 text-white px-3 py-2 rounded-lg text-sm font-medium whitespace-nowrap shadow-xl",children:["Chat dengan Kami",e.jsx("div",{className:"absolute right-0 top-1/2 -translate-y-1/2 translate-x-full",children:e.jsx("div",{className:"border-8 border-transparent border-l-gray-900"})})]})})]})]})]})}export{F as default};
