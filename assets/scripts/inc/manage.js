/*
    Description: AD survey admin manage page scripts
    Author URI: adillice.com
    Author: adillice
    Version: 1.0.0
*/

// import utility
import Utility from "./utility";

// manage class
class Manage extends Utility{

    // constructor
    constructor({
        nonceID = "ad-survey-manage-nonce",
        urlClass = "adsurvery__manage__wrapper__url",
        ajaxAction = "ad_survey_manage_delete",
        shortCodeBttnClass = "--copy-bttn",
        deleteSurveyBttnClass = "--delete-bttn",
        responseArea = "--survey-response",
        loadingClass = "--survey-loading",
        copiedClass = "--survey-copied",
        errorClass = "--survey-error",
    } = {}){

        // inherit class
        super();

        // error msg when no 
        this.errorMsg = "There are no more surveys left. Please re-import your survey questions and try again.";

        // admin url field  
        this.url = document.querySelector(`.${urlClass}`);

        // nonce field  
        this.nonce = document.querySelector(`#${nonceID}`);

        // delete button
        this.deleteBttn = document.querySelectorAll(`.${deleteSurveyBttnClass}`);

        // shortcode button
        this.shortCodeBttn = document.querySelectorAll(`.${shortCodeBttnClass}`);

        // ajax response area
        this.response = document.querySelector(`.${responseArea}`);

        // loading class
        this.loading = loadingClass;

        // copied class
        this.copied = copiedClass;

        // ajax action url
        this.action = ajaxAction;

        // error class
        this.error = errorClass;

        // timers
        this.timers = [];

        // delay time [seconds]
        this.delay = 0.7;

        // events
        this.events();

    }

    // run all events
    events(){

        // clicked delete button
        this.deleteBttn.forEach((bttn) => {bttn.addEventListener("touchstart", this.deleted.bind(this))});
        this.deleteBttn.forEach((bttn) => {bttn.addEventListener("click", this.deleted.bind(this))});
        
        // clicked delete button
        this.shortCodeBttn.forEach((bttn) => {bttn.addEventListener("touchstart", this.shortcode.bind(this))});
        this.shortCodeBttn.forEach((bttn) => {bttn.addEventListener("click", this.shortcode.bind(this))});

    }

    // delete button event
    deleted(e){
        
        // prevent default 
        e.preventDefault();

        // get target
        const target = e.target,

        // get li row
        li = target.parentNode.parentNode,

        // init final output
        output = {},

        // create form obj
        data = new FormData();

        // update class
        target.classList.add(this.loading);

        // set admin action
        output.action = this.action;
        
        // get admin ajax url
        output.url = (this.url && this.url.value) ? this.url.value : null;

        // update form data
        data.append("survey", (target && target.parentNode && target.parentNode.dataset.num) ? target.parentNode.dataset.num : null);
        data.append("nonce", (this.nonce && this.nonce.value) ? this.nonce.value : null);

        // add form data to output
        output.data = data;

        // make ajax call
        this.post(output).then((response) => {

            // set results to response
            const results = JSON.parse(response);

            // SUCCESS
            if(results.success && this.response){
                
                // remove row
                this.removeSurveyRow(li);

                // add response msg
                this.displayMessage(results.success);

            }

            // ERROR
            if(results.error){

                // add response msg - [param[is error] = true]
                this.displayMessage(results.error, true);

            }

            // update class
            target.classList.remove(this.loading);

        // error
        },(error) => {

            // simple console error msg
            console.error("ERROR:", error);
            
            // update class
            target.classList.remove(this.loading);

        });

    }

    // shortcode button event
    shortcode(e){
        
        // prevent default 
        e.preventDefault();

        // get target
        const target = e.target,

        // get data 
        shortcode = `[${target.parentNode.dataset.num}]`;

        // update class
        target.classList.add(this.copied);

        // remove icon
        this.delayIconRemoval(target, this.copied);

        // copy shortcode
        this.copyShortCode(shortcode);

    }

    // copy shortcode 
    copyShortCode(copy = null){

        // end case
        if(copy == null) return;

        // check if navigator is available
        if(!navigator.clipboard){

            // run backup option
            this.legacyCopyText(copy);

            // return
            return;
            
        }

        // copy text
        navigator.clipboard.writeText(copy).then(function(){
            
            // display success msg in console
            console.log("[Navigator] Copied shortcode to clipboard:", copy);
        
        // ERROR
        },function(error){
            
            // display error msg in console
            console.error("[Navigator] Could not copy text:", error);
            
        });

    }

    // backup 
    legacyCopyText(copy){

        // create text area
        const block = document.createElement("textarea").style.display = "none";

        // set copy
        block.value = copy;

        // append to body 
        document.body.appendChild(block);

        // focus
        block.focus();

        // select
        block.select();

        // try
        try{
            
            // run exec command
            const exeCmd = document.execCommand("copy");

            // display success msg
            console.log("[Legacy] Copied shortcode to clipboard:", copy);
            
        // catch error
        }catch(error){
            
            // display errors in console
            console.error("[Legacy] Could not copy shortcode to clipboard:", error);

        }

        // remove created block
        document.body.removeChild(block);

    }

    // remove specific survey from manage page
    removeSurveyRow(li = null){

        // end case
        if(li === null) return;

        // get ul element
        const ul = li.parentNode;

        // remove li element
        li.remove();

        // get ul wrapper element
        const wrapper = ul.parentNode,

        // get li count
        liCount = ul.querySelectorAll("li").length;

        // check for LI count
        if(liCount <= 0){

            // remove empty ul tag
            ul.remove();
            
            // create p tag
            const p = document.createElement("p");

            // add error class
            p.classList.add(this.error);

            // add error copy
            p.textContent = this.errorMsg;

            // add p tag to wrapper
            wrapper.appendChild(p);

        };

        // return
        return;

    }

    // delay icon swap
    delayIconRemoval(dom = null, swapClass = null){

        // end case
        if((dom == null)||(swapClass == null)) return;

        // set this to self
        const self = this;

        // create ID
        const id = Date.now().toString(36) + Math.random().toString(36).substring(2, 12).padStart(12, 0);

        // set timer to var
        self.timers[id] = setTimeout(function(){

            // remove class
            dom.classList.remove(swapClass);

            // delete timer func in our array 
            if(self.timers[id]) delete self.timers[id];

        // delay time
        }, (self.delay * 1000));

    }

    // display message
    displayMessage(copy = null, error = false){

        // end case
        if(copy === null) return;

        // clear response area
        this.response.replaceChildren();

        // create div
        const div = document.createElement("div"),

        // create p tag
        p = document.createElement("p");

        // add popup classes
        (!error) ? div.classList.add("notice", "notice-success", "is-dismissible") : div.classList.add("notice", "notice-error", "is-dismissible");

        // add copy to p tag
        p.textContent = copy;

        // append p to div
        div.appendChild(p)

        // add to result/response DOM element
        return this.response.appendChild(div);

    }

}


// export
export default Manage;