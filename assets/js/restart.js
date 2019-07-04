
$(document).ready(function() {
   localStorage.setItem("statusPage", 'Online'); 
   limitRestart();
});

function limitRestart() {

   var timeout;
   document.onmousemove = function() {
          clearTimeout(timeout);
          timeout = setTimeout(function() {
          location.href= urlhome + "desconectado";
          localStorage.setItem("statusPage", 'Offline');
          }, 120000);
    }

}