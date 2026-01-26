window.addEventListener("load", () => {
    let form = document.getElementById('join_form')

    form.addEventListener('submit', function(e){
        e.preventDefault();
        const formData = new FormData(this);
        const data = new FormData();
        formData.forEach((value, key) => {
            data.append(key, value);
        });

        data.append("action", "join_party");
        data.append("id_player", sessionStorage.getItem("session_id"));

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
                    window.location.replace(`../pages/lobby?id_game=${data.id_game}`);
                    
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