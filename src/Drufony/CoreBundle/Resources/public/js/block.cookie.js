window.onload = function() {
    if (document.getElementById('cookie-button')) {
        document.getElementById('cookie-button').onclick = function() {
            cookieDomain = this.getAttribute('data-cookie-domain');
            document.cookie = 'cookie-accept=1;domain='+cookieDomain+';path=/';
            document.getElementById('cookie-container').style.visibility = 'hidden';
            /**the next lines are useful to extend the functionality of accept button.
             * A function called onCookieTermAccept could be implemented in 
             * CustomProjectBundle, if we want to. 
             * **/
            if (typeof(onCookieTermAccept) == "function") {//check if callback function is defined
                onCookieTermAccept();  //if it is defined then call it.
            }
        }
    }
}
