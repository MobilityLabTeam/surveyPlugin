/*
    Description: js entry point
    Author URI: adillice.com
    Author: adillice
    Version: 1.0.0
*/

// use strict
'use strict';

// import files
import "../css/main.scss";
import Survey from "./inc/survey";

// on ready
const onReady = (callback) =>{
    
    // check readystate
    if(document.readyState != 'loading'){

        // run callback
        callback();

    // else
    }else if(document.addEventListener){

        // add event listener
        document.addEventListener('DOMContentLoaded', callback);

    // final
    }else{

        // attach event 
        document.attachEvent('onreadystatechange', function(){

            // check ready state
            if(document.readyState == 'complete') callback();

        });

    }
    
};

// wait for page to load...
onReady(() => {

    // frontend scripts 
    const survey = new Survey();

});