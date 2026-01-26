if (localStorage.getItem('session_id')){
    window.location.replace('./pages/home.html');
}else{
    window.location.replace('./pages/login.html');
}