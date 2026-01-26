window.addEventListener("load", () => {

    let form = document.getElementById('create_account');
    form.addEventListener('submit', (e) =>{

        e.preventDefault(); // Empêche le rechargement de la page

        const formData = new FormData(form);
        const data = new FormData();
        formData.forEach((value, key) => {
            data.append(key, value);
        });

        data.append("action", "create_account");

        fetch("../api/api.php", {
            method: "POST",
            body: data
        })
            .then(response => {
                if (!response.ok) throw new Error(`ERREUR : ${response.status}`);
                return response.json();
            })
            .then(data => {
                console.log(data);
                if (data.status == "succes") {
                    sessionStorage.setItem('session_id', data.session_id);
                    setTimeout(() => {
                        window.location.replace("../pages/home.html"); // Redirection après succès
                    }, 150);
                }
                else {
                    let message = document.getElementById("message");
                    message.innerHTML = data.message;
                    message.style.color = "red";

                }
            })
            .catch(error => console.error("Erreur :", error));
    });
});