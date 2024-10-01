/*
    Description: ultility class
    Author URI: adillice.com
    Author: adillice
    Version: 1.0.0
*/

// utility helper
class Utility{

    // constructor
    constructor(){
        // nothing to do yet...
    }

    // turn form data to query string
    queryStringData(formObj){

        // copy to array
        const data = [...formObj.entries()];

        // return mapped query string
        return data.map(value => `${encodeURIComponent(value[0])}=${encodeURIComponent(value[1])}`).join("&");
        
    }

    // do a post call
    async post(obj = null){
        
        // make sure we have url and data
        if(!obj || obj == null) return;

        // get action
        const action = obj.action,
        
        // get url
        url = obj.url,
        
        // get data
        data = obj.data,

        // convert values to query string
        query = (data) ? this.queryStringData(data) : null;

        // make sure we have data
        if(query == null) return;

        //init XML request
        const xhr = new XMLHttpRequest();

        // make a promise - resolve:error
        let promise = new Promise((res, err) =>{
            
            // init xhr
            xhr.open("POST", `${url}?action=${action}`, true);

            // set header
            xhr.setRequestHeader("Access-Control-Allow-Headers", "*");
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');

            // check on state change
            xhr.addEventListener("readystatechange", function(){
                
                // request complete
                if(this.readyState === XMLHttpRequest.DONE){

                    // status 0-299 should be ok
                    if(this.status < 300){
                        
                        //set response
                        res(this.responseText);

                    // somethings not right
                    }else{

                        // set error
                        err(this.statusText)
                    
                    }

                }

            });

            //send data
            xhr.send(query);

        });

        //return promise
        return promise;

    }

}

// export
export default Utility;