// /*
//     Description: walk js entry point
//     Author URI: adillice.com
//     Author: adillice
//     Version: 1.0.0
// */

// use strict
'use strict';

// import files
import "../css/admin.scss";
import Manage from "../scripts/inc/manage";
import Misc from "../scripts/inc/miscellaneous";

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

    // manage script
    const manage = new Manage();

    // misc script
    const misc = new Misc();

});