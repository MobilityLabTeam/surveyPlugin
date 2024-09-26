(()=>{"use strict";const s=class{constructor(){}queryStringData(s){return[...s.entries()].map((s=>`${encodeURIComponent(s[0])}=${encodeURIComponent(s[1])}`)).join("&")}async post(s=null){if(!s||null==s)return;const t=s.action,e=s.url,i=s.data,n=i?this.queryStringData(i):null;if(null==n)return;const r=new XMLHttpRequest;return new Promise(((s,i)=>{r.open("POST",`${e}?action=${t}`,!0),r.setRequestHeader("Access-Control-Allow-Headers","*"),r.setRequestHeader("Content-Type","application/x-www-form-urlencoded; charset=UTF-8"),r.addEventListener("readystatechange",(function(){this.readyState===XMLHttpRequest.DONE&&(this.status<300?s(this.responseText):i(this.statusText))})),r.send(n)}))}},t=class extends s{constructor({questionsClass:s="ads__questions__form__ul__li",guiClass:t="ads__questions__form__ui",msgClass:e="ads__questions__form__ui__msg",closeClass:i="ads__questions__close",prevClass:n="ads__questions__form__ui__wrapper__prev",nextClass:r="ads__questions__form__ui__wrapper__next",submitClass:u="ads__questions__form__ui__submit",countClass:h="ads__questions__form__ui__wrapper__copy > span",questionHideClass:o="--ad-survey-hide",semiTransparentClass:a="--ad-survey-semi",loadingClass:l="--ad-survey-load",successClass:c="--ad-survey-msg-success",errorClass:d="--ad-survey-msg-error",allowedInputs:p="input[type='text'], input[type='checkbox'], input[type='radio']",saveAction:m="ad_survey_data"}={}){super(),this.questions=document.querySelectorAll(`.${s}`),this.msg=document.querySelector(`.${e}`),this.gui=document.querySelector(`.${t}`),this.close=document.querySelector(`.${i}`),this.parent=this.questions[0]&&this.questions[0].parentNode?this.questions[0].parentNode:null,this.total=this.questions?this.questions.length:0,this.count=this.gui?this.gui.querySelector(`.${h}`):null,this.prev=this.gui?this.gui.querySelector(`.${n}`):null,this.next=this.gui?this.gui.querySelector(`.${r}`):null,this.submit=this.gui?this.gui.querySelector(`.${u}`):null,this.current=this.total>0?1:0,this.semi=a,this.hide=o,this.success=c,this.error=d,this.load=l,this.allowed=p,this.action=m,this.events()}events(){this.close&&this.close.addEventListener("touchstart",this.closeSurvey.bind(this)),this.close&&this.close.addEventListener("click",this.closeSurvey.bind(this)),this.prev&&this.prev.addEventListener("touchstart",this.prevQuestion.bind(this)),this.prev&&this.prev.addEventListener("click",this.prevQuestion.bind(this)),this.next&&this.next.addEventListener("touchstart",this.nextQuestion.bind(this)),this.next&&this.next.addEventListener("click",this.nextQuestion.bind(this)),this.parent&&this.parent.addEventListener("touchstart",this.userAnsweredCheck.bind(this)),this.parent&&this.parent.addEventListener("click",this.userAnsweredCheck.bind(this)),this.parent&&this.parent.addEventListener("keyup",this.userAnsweredCheck.bind(this)),this.gui&&this.gui.parentNode&&this.gui.parentNode.addEventListener("submit",this.process.bind(this))}closeSurvey(s){s.preventDefault();const t=this.close.parentNode;t&&t.remove()}prevQuestion(s){!this.prev||this.current<=1||"0"===this.questions[this.current-1].dataset.num||(this.moveQuestions(s,-1),this.displayButtons())}nextQuestion(s){!this.next||this.current>=this.total||"0"===this.questions[this.current-1].dataset.num||(this.moveQuestions(s),this.displayButtons())}moveQuestions(s,t=1){s.preventDefault(),this.questions[this.current-1].classList.add(this.hide),this.current=this.current&&this.current<=this.total?this.current+1*t:this.total,this.questions[this.current-1].classList.remove(this.hide),this.count.textContent=this.current}userAnsweredCheck(s){if(!s.target.matches(this.allowed))return;const t=this.questions[this.current-1].querySelectorAll(this.allowed);for(const s of t){let t=s.type.toLowerCase();if(this.questions[this.current-1].dataset.num=0,this.displayButtons(!0),"radio"===t||"checkbox"===t){if(s.checked){this.questions[this.current-1].dataset.num=1,this.displayButtons();break}}else if(s.value&&s.value.length>3){this.questions[this.current-1].dataset.num=1,this.displayButtons();break}}this.checkAllAnswers()}checkAllAnswers(){let s=0;for(const t of this.questions)t.dataset&&t.dataset.num>0&&s++;this.displaySubmit(s>=this.total)}displayButtons(s=!1){if(0==this.questions[this.current-1].dataset.num)return this.prev.classList.add(this.semi),void this.next.classList.add(this.semi);s?(this.prev.classList.add(this.semi),this.next.classList.add(this.semi)):(this.prev.classList.remove(this.semi),this.next.classList.remove(this.semi)),1===this.current&&this.prev.classList.add(this.semi),this.current===this.total&&this.next.classList.add(this.semi)}displaySubmit(s=!1){s?this.submit.classList.remove(this.hide):this.submit.classList.add(this.hide)}process(s){s.preventDefault();const t=s.target,e=new FormData(t),i=this.msg,n=this.submit,r=[];n.classList.add(this.load),r.url=t&&t.action?t.action:null,r.action=this.action,r.data=e,this.post(r).then((s=>{const t=JSON.parse(s);if(n.classList.remove(this.load),t.success){const s=this.questions[0]?this.questions[0].parentNode:null,e=this.prev?this.prev.parentNode:null;return this.buildSuccessMsg(s,e,n,i,t.success,!0)}if(t.error)return this.buildSuccessMsg(null,null,null,i,t.error)}),(s=>(n.classList.remove(this.load),this.buildSuccessMsg(null,null,null,i,s))))}buildSuccessMsg(s=null,t=null,e=null,i=null,n=null,r=!1){if(null==i||null==n)return;null!=s&&s.remove(),null!=t&&t.remove(),null!=e&&e.remove();const u=document.createElement("p");r?u.classList.add(this.success):u.classList.add(this.error),i.innerHTML="",u.textContent=n,i.appendChild(u),i.classList.remove(this.hide)}};jQuery((function(){new t}))})();
//# sourceMappingURL=main.js.map