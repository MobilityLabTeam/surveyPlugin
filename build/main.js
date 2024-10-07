(()=>{"use strict";const t=class{constructor(){}queryStringData(t){return[...t.entries()].map((t=>`${encodeURIComponent(t[0])}=${encodeURIComponent(t[1])}`)).join("&")}async post(t=null){if(!t||null==t)return;const s=t.action,e=t.url,i=t.data,n=i?this.queryStringData(i):null;if(null==n)return;const r=new XMLHttpRequest;return new Promise(((t,i)=>{r.open("POST",`${e}?action=${s}`,!0),r.setRequestHeader("Access-Control-Allow-Headers","*"),r.setRequestHeader("Content-Type","application/x-www-form-urlencoded; charset=UTF-8"),r.addEventListener("readystatechange",(function(){this.readyState===XMLHttpRequest.DONE&&(this.status<300?t(this.responseText):i(this.statusText))})),r.send(n)}))}},s=class extends t{constructor({questionsClass:t="ads__questions__form__ul__li",guiClass:s="ads__questions__form__ui",msgClass:e="ads__questions__form__ui__msg",closeClass:i="ads__questions__close",prevClass:n="ads__questions__form__ui__wrapper__prev",nextClass:r="ads__questions__form__ui__wrapper__next",submitClass:u="ads__questions__form__ui__submit",countClass:h="ads__questions__form__ui__wrapper__copy > span",questionHideClass:a="--ad-survey-hide",semiTransparentClass:o="--ad-survey-semi",loadingClass:l="--ad-survey-load",successClass:c="--ad-survey-msg-success",errorClass:d="--ad-survey-msg-error",allowedInputs:p="input[type='text'], input[type='checkbox'], input[type='radio']",saveAction:m="ad_survey_data"}={}){super(),this.questions=document.querySelectorAll(`.${t}`),this.msg=document.querySelector(`.${e}`),this.gui=document.querySelector(`.${s}`),this.close=document.querySelector(`.${i}`),this.parent=this.questions[0]&&this.questions[0].parentNode?this.questions[0].parentNode:null,this.total=this.questions?this.questions.length:0,this.count=this.gui?this.gui.querySelector(`.${h}`):null,this.prev=this.gui?this.gui.querySelector(`.${n}`):null,this.next=this.gui?this.gui.querySelector(`.${r}`):null,this.submit=this.gui?this.gui.querySelector(`.${u}`):null,this.current=this.total>0?1:0,this.semi=o,this.hide=a,this.success=c,this.error=d,this.load=l,this.allowed=p,this.action=m,this.events()}events(){this.close&&this.close.addEventListener("touchstart",this.closeSurvey.bind(this)),this.close&&this.close.addEventListener("click",this.closeSurvey.bind(this)),this.prev&&this.prev.addEventListener("touchstart",this.prevQuestion.bind(this)),this.prev&&this.prev.addEventListener("click",this.prevQuestion.bind(this)),this.next&&this.next.addEventListener("touchstart",this.nextQuestion.bind(this)),this.next&&this.next.addEventListener("click",this.nextQuestion.bind(this)),this.parent&&this.parent.addEventListener("touchstart",this.userAnsweredCheck.bind(this)),this.parent&&this.parent.addEventListener("click",this.userAnsweredCheck.bind(this)),this.parent&&this.parent.addEventListener("keyup",this.userAnsweredCheck.bind(this)),this.gui&&this.gui.parentNode&&this.gui.parentNode.addEventListener("submit",this.process.bind(this))}closeSurvey(t){t.preventDefault();const s=this.close.parentNode;s&&s.remove()}prevQuestion(t){!this.prev||this.current<=1||"0"===this.questions[this.current-1].dataset.num||(this.moveQuestions(t,-1),this.displayButtons())}nextQuestion(t){!this.next||this.current>=this.total||"0"===this.questions[this.current-1].dataset.num||(this.moveQuestions(t),this.displayButtons())}moveQuestions(t,s=1){t.preventDefault(),this.questions[this.current-1].classList.add(this.hide),this.current=this.current&&this.current<=this.total?this.current+1*s:this.total,this.questions[this.current-1].classList.remove(this.hide),this.count.textContent=this.current}userAnsweredCheck(t){if(!t.target.matches(this.allowed))return;const s=this.questions[this.current-1].querySelectorAll(this.allowed);for(const t of s){let s=t.type.toLowerCase();if(this.questions[this.current-1].dataset.num=0,this.displayButtons(!0),"radio"===s||"checkbox"===s){if(t.checked){this.questions[this.current-1].dataset.num=1,this.displayButtons();break}}else if(t.value&&t.value.length>3){this.questions[this.current-1].dataset.num=1,this.displayButtons();break}}this.checkAllAnswers()}checkAllAnswers(){let t=0;for(const s of this.questions)s.dataset&&s.dataset.num>0&&t++;this.displaySubmit(t>=this.total)}displayButtons(t=!1){if(this.prev&&this.next&&this.semi){if(0==this.questions[this.current-1].dataset.num)return this.prev.classList.add(this.semi),void this.next.classList.add(this.semi);t?(this.prev.classList.add(this.semi),this.next.classList.add(this.semi)):(this.prev.classList.remove(this.semi),this.next.classList.remove(this.semi)),this.current&&1===this.current&&this.prev.classList.add(this.semi),this.current&&this.current===this.total&&this.next.classList.add(this.semi)}}displaySubmit(t=!1){this.submit&&(t?this.submit.classList.remove(this.hide):this.submit.classList.add(this.hide))}process(t){t.preventDefault();const s=t.target,e=new FormData(s),i=this.msg,n=this.submit,r=[];n.classList.add(this.load),r.url=s&&s.action?s.action:null,r.action=this.action,r.data=e,this.post(r).then((t=>{const s=JSON.parse(t);if(n.classList.remove(this.load),s.success){const t=this.questions[0]?this.questions[0].parentNode:null,e=this.prev?this.prev.parentNode:null;return this.buildSuccessMsg(t,e,n,i,s.success,!0)}if(s.error)return this.buildSuccessMsg(null,null,null,i,s.error)}),(t=>(n.classList.remove(this.load),this.buildSuccessMsg(null,null,null,i,t))))}buildSuccessMsg(t=null,s=null,e=null,i=null,n=null,r=!1){if(null==i||null==n)return;null!=t&&t.remove(),null!=s&&s.remove(),null!=e&&e.remove();const u=document.createElement("p");r?u.classList.add(this.success):u.classList.add(this.error),i.innerHTML="",u.textContent=n,i.appendChild(u),i.classList.remove(this.hide)}};var e;e=()=>{new s},"loading"!=document.readyState?e():document.addEventListener?document.addEventListener("DOMContentLoaded",e):document.attachEvent("onreadystatechange",(function(){"complete"==document.readyState&&e()}))})();
//# sourceMappingURL=main.js.map