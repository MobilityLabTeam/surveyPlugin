/*
    Description: AD survey admin miscellaneous class scripts
    Author URI: adillice.com
    Author: adillice
    Version: 1.0.0
*/

// admin miscellaneous class
class Miscellaneous{

    // constructor
    constructor({
        exportFormButton = "adsurvery__export__wrapper__form__submit > input[type='submit']",
        importFormButton = "adsurvery__import__wrapper__form__submit > input[type='submit']",
        loadingClass = "--admin-survey-loading",
    } = {}){

        // export bttn  
        this.exportBttn = document.querySelector(`.${exportFormButton}`);

        // import bttn  
        this.importBttn = document.querySelector(`.${importFormButton}`);
        
        // loading class
        this.loading = loadingClass;

        // set timer
        this.timer = null;

        // delay time
        this.delay = 1.9;

        // events
        this.events();

    }

    // run all events
    events(){
        
        // clicked export button
        if(this.exportBttn) this.exportBttn.addEventListener("touchstart", this.exportProcess.bind(this));
        if(this.exportBttn) this.exportBttn.addEventListener("click", this.exportProcess.bind(this));
        
        // clicked import button
        if(this.importBttn) this.importBttn.addEventListener("touchstart", this.addLoadingIcon.bind(this));
        if(this.importBttn) this.importBttn.addEventListener("click", this.addLoadingIcon.bind(this));

    }

    // [FAKE] export process
    exportProcess(e){

        // get target
        const target = e.target,

        // set this
        self = this;
        
        // add loading gif
        target.classList.add(self.loading);

        // clear timeout
        clearTimeout(self.time);

        // set time
        self.time = setTimeout(function(){
            
            // remove loading gif
            self.exportBttn.classList.remove(self.loading);

        },(self.delay * 1000))

    }

    // add loadin
    addLoadingIcon(e){
        
        // set target
        const target = e.target;
        
        // add loading gif
        target.classList.add(this.loading);

    }

}

// export
export default Miscellaneous;