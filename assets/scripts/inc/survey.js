/*
    Description: AD survey user (frontend) scripts
    Author URI: adillice.com
    Author: adillice
    Version: 1.0.0
*/

// import utility
import Utility from "./utility";

// manage class
class Survey extends Utility{

    // constructor
    constructor({

        // questions class
        questionsClass = "ads__questions__form__ul__li",

        // gui class
        guiClass = "ads__questions__form__ui",

        // msg area class
        msgClass = "ads__questions__form__ui__msg",

        // close class
        closeClass = "ads__questions__close",

        // prev class
        prevClass = "ads__questions__form__ui__wrapper__prev",

        // next class
        nextClass = "ads__questions__form__ui__wrapper__next",

        // submit class
        submitClass = "ads__questions__form__ui__submit",

        // count text class
        countClass = "ads__questions__form__ui__wrapper__copy > span",

        // question hide class
        questionHideClass = "--ad-survey-hide",

        // semi transparent class
        semiTransparentClass = "--ad-survey-semi",

        // loading icon class
        loadingClass = "--ad-survey-load",

        // msg success class
        successClass = "--ad-survey-msg-success",

        // msg error class
        errorClass = "--ad-survey-msg-error",

        // allowed survey user input types
        allowedInputs = "input[type='text'], input[type='checkbox'], input[type='radio']",

        // survey save action
        saveAction = "ad_survey_data",

    } = {}){

        // super
        super();

        // get all questions
        this.questions = document.querySelectorAll(`.${questionsClass}`);

        // get msg area
        this.msg = document.querySelector(`.${msgClass}`);

        // get gui
        this.gui = document.querySelector(`.${guiClass}`);

        // close button
        this.close = document.querySelector(`.${closeClass}`);

        // get questions parent element (list)
        this.parent = (this.questions[0] && this.questions[0].parentNode) ? this.questions[0].parentNode : null;

        // get total question count
        this.total = (this.questions) ? this.questions.length : 0;
        
        // count copy
        this.count = (this.gui) ? this.gui.querySelector(`.${countClass}`) : null; 

        // prev bttn
        this.prev = (this.gui) ? this.gui.querySelector(`.${prevClass}`) : null;
        
        // next bttn
        this.next = (this.gui) ? this.gui.querySelector(`.${nextClass}`) : null;

        // submit bttn
        this.submit = (this.gui) ? this.gui.querySelector(`.${submitClass}`) : null;

        // get current question
        this.current = (this.total > 0) ? 1 : 0;

        // semi transparent
        this.semi = semiTransparentClass;

        // question hide
        this.hide = questionHideClass;

        // success msg
        this.success = successClass; 

        // error msg
        this.error = errorClass; 

        // load icon
        this.load = loadingClass;

        // allowed user inputs
        this.allowed = allowedInputs;

        // survey save action
        this.action = saveAction;

        // run events
        this.events();

    }

    // fire events
    events(){

        // click events
        if(this.close) this.close.addEventListener("touchstart", this.closeSurvey.bind(this));
        if(this.close) this.close.addEventListener("click", this.closeSurvey.bind(this));
        if(this.prev) this.prev.addEventListener("touchstart", this.prevQuestion.bind(this));
        if(this.prev) this.prev.addEventListener("click", this.prevQuestion.bind(this));
        if(this.next) this.next.addEventListener("touchstart", this.nextQuestion.bind(this));
        if(this.next) this.next.addEventListener("click", this.nextQuestion.bind(this));
        if(this.parent) this.parent.addEventListener("touchstart", this.userAnsweredCheck.bind(this));
        if(this.parent) this.parent.addEventListener("click", this.userAnsweredCheck.bind(this));
        if(this.parent) this.parent.addEventListener("keyup", this.userAnsweredCheck.bind(this));
        if(this.gui && this.gui.parentNode) this.gui.parentNode.addEventListener("submit", this.process.bind(this));

    }

    // close overlay
    closeSurvey(e){

        // prevent default
        e.preventDefault();

        // close bttn
        const bttn = this.close,

        // survey wrapper
        wrapper = bttn.parentNode;

        // remove wrapper
        if(wrapper) wrapper.remove();

    }

    // prev question
    prevQuestion(e){

        // end case
        if(!this.prev || (this.current <= 1) || (this.questions[this.current - 1].dataset.num === "0")) return;

        // go to next question
        this.moveQuestions(e, -1);
        
        // show/hide buttons
        this.displayButtons();

    }

    // next question
    nextQuestion(e){

        // end case
        if(!this.next || (this.current >= this.total) || (this.questions[this.current - 1].dataset.num === "0")) return;

        // go to next question
        this.moveQuestions(e);

        // show/hide buttons
        this.displayButtons();

    }

    // move forward or back
    moveQuestions(e, dir = 1){

        // prevent default
        e.preventDefault();

        // hide current question
        this.questions[this.current - 1].classList.add(this.hide);

        // increment next question
        this.current = (this.current && (this.current <= this.total)) ? (this.current + (dir * 1)) : this.total;

        // show next question
        this.questions[this.current - 1].classList.remove(this.hide);

        // update count
        this.count.textContent = this.current;

    }

    // check if user answered current question
    userAnsweredCheck(e){

        // get current target
        const target = e.target;
        
        // do nothing if not an input
        if(!target.matches(this.allowed)) return;

        // allowed input types
        const allowed = this.questions[this.current - 1].querySelectorAll(this.allowed);
        
        // LOOP: thru item
        for(const item of allowed){

            // type
            let type = item.type.toLowerCase();

            // reset DOM proceed value
            this.questions[this.current - 1].dataset.num = 0;

            // reset bttn(s)
            this.displayButtons(true);

            // check for radio and checkboxes
            if((type === "radio" || type === "checkbox")){

                // checked if user picked something
                if(item.checked){

                    // enable proceed
                    this.questions[this.current - 1].dataset.num = 1;

                    // show bttn(s)
                    this.displayButtons();
                    
                    // exit loop
                    break;
                
                } 

            // all other input fields 
            }else{

                // checked if user wrote something
                if(item.value && item.value.length > 3){

                    // enable proceed
                    this.questions[this.current - 1].dataset.num = 1;

                    // show bttn(s)
                    this.displayButtons();
                    
                    // exit loop
                    break;
                
                }

            }

        }
        
        // tally total answer
        this.checkAllAnswers();

    }

    // check total answer
    checkAllAnswers(){

        // init total answer 
        let answered = 0;

        // LOOP: thru questions
        for(const data of this.questions){

            // data is 1
            if(data.dataset && (data.dataset.num > 0)) answered++;

        }

        // display submit bttn
        this.displaySubmit((answered >= this.total));

    }

    // button display status
    displayButtons(hide = false){

        // end case 
        if(this.questions[this.current - 1].dataset.num == 0){

            // hide classes
            this.prev.classList.add(this.semi);
            this.next.classList.add(this.semi);
            
            // return
            return;
            
        }

        // update bttns
        if(!hide){

            // show classes
            this.prev.classList.remove(this.semi);
            this.next.classList.remove(this.semi);
        
        }else{

            // hide classes
            this.prev.classList.add(this.semi);
            this.next.classList.add(this.semi);
        
        }

        // if we are on first question keep hidden
        if(this.current === 1) this.prev.classList.add(this.semi);
        
        // if we are on last question keep hidden
        if(this.current === this.total) this.next.classList.add(this.semi);

    }

    // display submit
    displaySubmit(show = false){

        // HIDE
        if(!show){

            // hide bttn
            this.submit.classList.add(this.hide);
            
        // SHOW
        }else{
            
            // show bttn 
            this.submit.classList.remove(this.hide);

        }

        // return
        return;

    }

    // process form
    process(e){

        // prevent default
        e.preventDefault();

        // get form
        const form = e.target,
        
        // get form data
        data = new FormData(form),

        // msg area
        msg = this.msg,

        // submit bttn
        bttn = this.submit, 

        // init output
        output = [];
        
        // add loading icon
        bttn.classList.add(this.load);
        
        // get admin ajax url
        output.url = (form && form.action) ? form.action : null;

        // set ajax action
        output.action = this.action;

        // add form data to output
        output.data = data;

        // make ajax call
        this.post(output).then((response) => {

            // set results to response
            const results = JSON.parse(response);

            // update class
            bttn.classList.remove(this.load);

            // SUCCESS
            if(results.success){

                // ul list
                const ulWrapper = (this.questions[0]) ? this.questions[0].parentNode : null,

                // gui wrapper
                guiWrapper = (this.prev) ? this.prev.parentNode : null;

                // build success msg
                return this.buildSuccessMsg(ulWrapper, guiWrapper, bttn, msg, results.success, true);

            }

            // ERROR(S)
            if(results.error){

                // build success msg
                return this.buildSuccessMsg(null, null, null, msg, results.error);

            }

        // error
        },(error) => {
            
            // update class
            bttn.classList.remove(this.load);

            // build success msg
            return this.buildSuccessMsg(null, null, null, msg, error);            

        });

    }

    // success message
    buildSuccessMsg(ul = null, gui = null, bttn = null, msg = null, copy = null, success = false){

        // end case
        if((msg == null) || (copy == null)) return;

        // remove questions
        if(ul != null) ul.remove();

        // remove gui
        if(gui != null) gui.remove();

        // remove bttn
        if(bttn != null) bttn.remove();

        // create p tag
        const p = document.createElement("p");
        
        // add class
        (success) ? p.classList.add(this.success) : p.classList.add(this.error)

        // reset msg
        msg.innerHTML = '';

        // add copy to p
        p.textContent = copy;

        // add p to msg
        msg.appendChild(p);

        // show msg area
        msg.classList.remove(this.hide);

    }

}

// export
export default Survey;