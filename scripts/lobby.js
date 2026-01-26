window.addEventListener("load", () => {
    const id_game = new URLSearchParams(window.location.search).get('id_game');
    let party_id = document.getElementById('party_id');
    party_id.innerHTML += id_game;

    let leave = document.getElementById('leave_game');
    leave.addEventListener('click', (e)=>{
        e.preventDefault();
        let data = new FormData;
        data.append("action", "leave_game");
        data.append("id_player", sessionStorage.getItem("session_id"));
        data.append("id_game", id_game);

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
               window.location.replace("../pages/home.html");
            }
            else {
                console.log(data.message);
            }
        })
        .catch(error => console.error("Erreur :", error));
    })
    

    let table_players = document.getElementById('player_list');
    player_list(table_players);

    setInterval(function(){
        player_list(table_players);
    }, 1000)

    let ready_btn = document.getElementById('ready_status');
    ready_btn.addEventListener('click', (e) => {
        change_ready(id_game);
    });

    let launch_btn = document.getElementById('launch');
    launch_btn.addEventListener('click', (e) =>{
        launch_game(id_game);
    });

    


    function player_list(table){
        var data= new FormData;
        data.append("action", "get_player_game")
        data.append("id_game", id_game);

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
                let nb_player_ready = 0;
                let count = 0;
                let idname = "player_";
                data.players.forEach(p =>{
                    count ++;
                    let new_line = document.getElementById(`${idname}${count}`);
                    new_line.innerHTML = '';
                    new_line.classList.remove('notset');
                    let player_box = document.createElement('td');
                    player_box.innerHTML = p.Username;
                    let prof_pic = document.createElement('img');
                    prof_pic.setAttribute('src', p.Profil_pic);
                    player_box.appendChild(prof_pic);
                    
                    new_line.appendChild(player_box);

                    let text;
                    let color;

                    if (p.Ready){
                        text = "Prêt";
                        color = "#00BD6B";
                        nb_player_ready ++
                    }else{
                        text = "Pas prêt";
                        color = "crimson";
                    }
                    
                    if (p.ID_Player == sessionStorage.getItem('session_id')){
                        ready_btn.style.backgroundColor = color;
                    }

                    let player_status = document.createElement('td');
                    player_status.style.color = color;
                    player_status.innerHTML = text;
                    player_status.setAttribute("id", `${p.ID_Player}_status`);
                    player_status.classList.add('status');

                    new_line.appendChild(player_status);
                    table.appendChild(new_line);
                });
                let nb_player_final = count;
                console.log(nb_player_final);
                

                while(count <= 6){
                    count++;
                    let refresh_line = document.getElementById(`player_${count}`);
                    if (refresh_line){
                        refresh_line.classList.add("notset");
                    }
                }

                get_game_info(id_game).then(nb_player_max => {
                    let player_count = document.getElementById('player_count');
                    player_count.innerHTML = `${nb_player_final}/${nb_player_max} joueurs`;
                    console.log(nb_player_ready, nb_player_max);
                    let launch_btn = document.getElementById('launch');
                    if (nb_player_ready == nb_player_max){
                        launch_btn.disabled = false;
                        launch_btn.style.backgroundColor = "#00BD6B";
                    }
                    else{
                        launch_btn.disabled = true;
                        launch_btn.style.backgroundColor = "#888888";
                    }
                });

            }
            else {
                console.log(data.message);
            }
        })
        .catch(error => console.error("Erreur :", error));
    }

    function get_game_info(id){
        var data= new FormData;
        data.append("action", "get_info_game")
        data.append("id_game", id);

        return fetch("../api/api.php", {
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
                if (data.game.Started){
                    window.location.replace(`../pages/game.html?id_game=${id}`);
                }else{
                    var nb_player_max = data.game.Nb_player_max;
                    return nb_player_max;
                }
                
            }
            else {
                console.log(data.message);
            }
        })
        .catch(error => console.error("Erreur :", error));
    }

    function change_ready(id){
        var data= new FormData;
        data.append("action", "change_ready")
        data.append("id_game", id);
        data.append("id_player", sessionStorage.getItem('session_id'));

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
                let ready_btn = document.getElementById('ready_status');
                let status = document.getElementById(`${sessionStorage.getItem('session_id')}_status`);
                console.log(sessionStorage.getItem('session_id'));
                let text;
                let newcolor;

                if (data.color == 'red'){
                    text = "Pas prêt";
                    newcolor = "#dc143c";
                }
                else{
                    text = "Prêt";
                    newcolor = "#00BD6B";
                }
                console.log(newcolor);
                
                ready_btn.style.backgroundColor = newcolor;
                status.innerHTML = text;
                status.style.color = newcolor;
            }
            else {
                console.log(data.message);
            }
        })
        .catch(error => console.error("Erreur :", error));
    }

    function launch_game(id){
        let data = new FormData;

        data.append('action', 'launch_game');
        data.append('id_game', id);

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
                    window.location.replace(`../pages/game.html?id_game=${id}`);
                }
            })
            .catch(error => console.error("Erreur :", error));
    }


});