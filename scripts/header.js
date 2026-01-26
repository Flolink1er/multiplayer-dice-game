window.addEventListener("load", () => {

    let check = new FormData;
    check.append('action', 'check_logged');

    fetch("../api/api.php", {
            method: "POST",
            body: check
        })
            .then(response => {
                if (!response.ok) throw new Error(`ERREUR : ${response.status}`);
                return response.json();
            })
            .then(check => {
                if (check.status == "succes") {
                    sessionStorage.setItem('session_id', check.id);
                }
                else {
                    window.location.replace("../pages/login.html");
                }
            })
            .catch(error => console.error("Erreur :", error));

    
    let navbar = document.createElement('nav');
    navbar.setAttribute('id', 'navbar');
    document.body.appendChild(navbar);
    let link_list = document.createElement('ul');
    
    let create_box = document.createElement('li');
    let go_to_create = document.createElement('a')
    go_to_create.setAttribute('href', '../pages/create_party.html');
    go_to_create.innerHTML = 'Créer une partie';
    create_box.appendChild(go_to_create);
    link_list.appendChild(create_box);

    let join_box = document.createElement('li');
    let go_to_join = document.createElement('a')
    go_to_join.setAttribute('href', '../pages/join_party.html');
    go_to_join.innerHTML = 'Rejoindre une partie';
    join_box.appendChild(go_to_join);
    link_list.appendChild(join_box);

    navbar.appendChild(link_list);

    let profil_box = document.createElement('div');
    profil_box.setAttribute('id', 'profil_box');


    let profil_pic = document.createElement('img');
    profil_pic.setAttribute('id', 'navppic');
    let username = document.createElement('h1');
    username.setAttribute("id", 'username_box');
    var data = new FormData();

    data.append("action", "get_profil");

    fetch("../api/api.php", {
        method: "POST",
        body: data
    })
        .then(response => {
            if (!response.ok) throw new Error(`ERREUR : ${response.status}`);
            return response.json();
        })
        .then(data => {
            if (data.status == "succes") {
                profil_pic.setAttribute('src', data.player['Profil_pic']);
                username.innerHTML = data.player['Username'];
            }
            else {
                console.log(data.message);
            }
        })
        .catch(error => console.error("Erreur :", error));

    

    profil_box.appendChild(profil_pic);
    profil_box.appendChild(username);

    navbar.appendChild(profil_box);

    let modify = document.createElement('button');
    modify.innerHTML = "&#9998;";
    modify.setAttribute('id', 'modify_profil');

    modify.addEventListener("click", () => modify_form());

    profil_box.appendChild(modify);

    let logout_button = document.createElement('button');
    logout_button.innerHTML = "Se déconnecter";
    logout_button.setAttribute('id', 'logout');

    logout_button.addEventListener("click",  () => logout());

    navbar.appendChild(logout_button);

    

    function modify_form(){

        var data = new FormData();

        data.append("action", "get_profil");
        data.append('id', sessionStorage.getItem("session_id"));

        fetch("../api/api.php", {
            method: "POST",
            body: data
        })
            .then(response => {
                if (!response.ok) throw new Error(`ERREUR : ${response.status}`);
                return response.json();
            })
            .then(data => {
                if (data.status == "succes") {
                    const form = document.createElement('form');
                    form.id = 'profileForm';
                    form.method = 'POST';
                    form.enctype = 'multipart/form-data'; // Nécessaire pour envoyer des fichiers (comme l'image)
            
                    // Créer un champ pour le nom
                    const usernameLabel = document.createElement('label');
                    usernameLabel.textContent = 'Username :';
                    const usernameInput = document.createElement('input');
                    usernameInput.type = 'text';
                    usernameInput.name = 'username';
                    usernameInput.value = data.player['Username'];
                    usernameInput.required = true;
                    
            
                    // Créer un champ pour l'email
                    const emailLabel = document.createElement('label');
                    emailLabel.textContent = 'Email :';
                    const emailInput = document.createElement('input');
                    emailInput.type = 'email';
                    emailInput.name = 'email';
                    emailInput.value = data.player['Email'];
                    emailInput.required = true;
            
                    // Créer un champ pour le mot de passe
                    const passwLabel = document.createElement('label');
                    passwLabel.textContent = 'Changer mot de passe :';
                    const passwInput = document.createElement('input');
                    passwInput.type = 'password';
                    passwInput.name = 'password';
                    passwInput.placeholder = 'Entrez un nouveau mot de passe';
                    passwInput.required = false;
            
                    // Créer un champ pour la photo de profil
                    const photoLabel = document.createElement('label');
                    photoLabel.textContent = 'Photo de profil :';
                    const photoInput = document.createElement('input');
                    photoInput.type = 'file';
                    photoInput.name = 'profil_picture';  // Le nom que tu vas utiliser pour récupérer l'image côté serveur
                    photoInput.accept = 'image/*'; // Accepte uniquement les fichiers image
                    photoInput.required = false; // Facultatif, à enlever si obligatoire
            
                    // Créer un bouton pour soumettre le formulaire
                    const submitButton = document.createElement('button');
                    submitButton.type = 'submit';
                    submitButton.textContent = 'Modifier mon profil';
            
                    const closeButton = document.createElement('button');
                    closeButton.type = '';
                    closeButton.textContent = 'Annuler';
                    
                    // Ajouter tous les éléments au formulaire
                    form.appendChild(usernameLabel);
                    form.appendChild(usernameInput);
                    form.appendChild(emailLabel);
                    form.appendChild(emailInput);
                    form.appendChild(passwLabel);
                    form.appendChild(passwInput);
                    form.appendChild(photoLabel);
                    form.appendChild(photoInput);
                    form.appendChild(submitButton);
                    form.appendChild(closeButton);
            
                    // Ajouter le formulaire au body ou à un conteneur spécifique de ta page
                    const form_container = document.createElement('div');
                    form_container.id = 'background';
            
                    form_container.appendChild(form);
                    document.body.appendChild(form_container);
            
                    // Optionnel : Ajouter un message ou un élément pour l'aperçu de la photo de profil
                    const imagePreview = document.createElement('img');
                    imagePreview.id = 'imagePreview';
                    imagePreview.style.maxWidth = '200px';
                    document.body.appendChild(imagePreview);
            
                    // Afficher un aperçu de la photo téléchargée
                    photoInput.addEventListener('change', function (e) {
                        const file = e.target.files[0];
                        if (file) {
                            const reader = new FileReader();
                            reader.onload = function(event) {
                                imagePreview.src = event.target.result;
                            };
                            reader.readAsDataURL(file);
                        }
                    });
            
                    closeButton.addEventListener('click', function(event) {
                        form_container.remove();
                        form.remove();
                    });
            
                    form.addEventListener('submit', function(event) {
                        event.preventDefault();
            
                        var formData = new FormData(form);
                        var data = new FormData();
                        formData.forEach((value, key) => {
                            data.append(key, value);
                        });
            
                        data.append('action', 'modify_profil');
            
                        fetch('../api/api.php', {
                            method: 'POST',
                            body: data
                        })
                        .then(response => response.json())
                        .then(data => {
                            if(data.status == "success") {
                                window.location.reload();
                            }
                            else {
                                console.log(data.message);
                                //connexionForm.classList.remove('in');
                            }
            
                        })
                        .catch(error => {
                            console.error('Erreur lors de la connexion :', error);
                        });
                    });
                }
                else {
                    console.log(data.message);
                }
            })
            .catch(error => console.error("Erreur :", error));
        
    }


    function logout(){
        const data = new FormData();

        data.append("action", "logout");

        fetch("../api/api.php", {
            method: "POST",
            body: data
        })
            .then(response => {
                if (!response.ok) throw new Error(`ERREUR : ${response.status}`);
                return response.json();
            })
            .then(data => {
                if (data.status == "success") {
                    console.log('hum');
                    
                    sessionStorage.clear();
                    setTimeout(() => {
                        window.location.replace("../pages/login.html"); // Redirection après succès
                    }, 150);
                }
                else {
                    console.log(data.message);
                }
            })
            .catch(error => console.error("Erreur :", error));
    }
});