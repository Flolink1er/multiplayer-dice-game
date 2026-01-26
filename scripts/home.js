window.addEventListener("load", () => {
    let data = new FormData();

    data.append("action", "get_history");

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
                let history = document.getElementById('history_table');
                data.games.forEach(game => {
                    let newline = document.createElement('tr');

                    let date = document.createElement('td');
                    let rank = document.createElement('td');
                    let eliar = document.createElement('td');

                    date.innerHTML = game.date;
                    date.classList.add('date_rank');
                    
                    if (game.Ranked == null && game.Finished == null){
                        if (game.Started == null){
                            let join_game = document.createElement('a');
                            rank.innerHTML = "Partie non commencée"
                            join_game.innerHTML = "Rejoindre le lobby"
                            join_game.target = "_blank";
                            join_game.href = `../pages/lobby?id_game=${game.ID_game}`;
                            eliar.appendChild(join_game);
                        }
                        else{
                            let join_game = document.createElement('a');
                            rank.innerHTML = "Partie non terminée";
                            join_game.innerHTML = "Continuer la partie";
                            join_game.href = `../pages/game?id_game=${game.ID_game}`;
                            eliar.appendChild(join_game);
                        }
                        eliar.classList.add('join')

                    }else{
                        rank.innerHTML = `${game.Ranked}/ ${game.Nb_player_max}`;;
                        eliar.innerHTML = (1+game.Nb_player_max) * (game.Nb_player_max - game.Ranked)
                    }

                    newline.appendChild(date);
                    newline.appendChild(rank);
                    newline.appendChild(eliar);

                    history.appendChild(newline);
                });
    
            }
            else if(data.status == "nomatch"){
                let message = document.getElementById('history');
                let newline = document.createElement('div');
                newline.innerHTML = "Aucune partie dans votre historique";
                message.appendChild(newline);
            }
            else{
                console.log(data.message);
            }
        })
        .catch(error => console.error("Erreur :", error))
    ;



    let newdata= new FormData();
    
    newdata.append("action", "get_ranking");

    fetch("../api/api.php", {
        method: "POST",
        body: newdata
    })
        .then(response => {
            if (!response.ok) throw new Error(`ERREUR : ${response.status}`);
            return response.json();
        })
        .then(data => {
            console.log(data);

            if (data.status == "succes") {
                if (data.noranks){
                    let message = document.getElementById('ranking');
                    let newline = document.createElement('div');
                    newline.innerHTML = data.message;
                    message.appendChild(newline);
                }
                else{

                    let rank = document.getElementById('rank_table');
                    let count = 1;
                    data.ranks.forEach(player => {
                        let newline = document.createElement('tr');

                        let classement = document.createElement('td');
                        let username = document.createElement('td');
                        let eliar = document.createElement('td');

                        classement.classList.add('ranks')
                        classement.innerHTML = `&#${9855+count};`;

                        username.classList.add('rank_username');
                        username.innerHTML = player.Username;
                        eliar.innerHTML = player.Eliar;

                        newline.appendChild(classement);
                        newline.appendChild(username);
                        newline.appendChild(eliar);

                        rank.appendChild(newline);
                        count++
                    });
                }
                
            }
            else {
                console.log(data.message);
            }
        })
        .catch(error => console.error("Erreur :", error));





    
});