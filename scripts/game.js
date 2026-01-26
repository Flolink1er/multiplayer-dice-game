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

    const id_game = new URLSearchParams(window.location.search).get('id_game');
    var min_nb_guess;
    var min_digit_guess;
    const game = setInterval(function(){
        get_players_info(id_game);
        get_current_guess(id_game);
        get_turn(id_game);
        get_event(id_game);
        get_player_dice(id_game);
        check_dual(id_game);
        check_endgame(id_game);
    },1000);

    let guess_maker = document.getElementById('guess_maker');
    guess_maker.addEventListener('submit', function(e){
        e.preventDefault();

        let formData = new FormData(this);
        let data = new FormData;
        formData.forEach((value, key) => {
            data.append(key, value);
        })
        data.append('action', 'new_guess') 
        data.append('id_game', id_game);     
        let msg = document.getElementById('message');

        if ((data.get('nb_dice') > min_nb_guess && (data.get('dice_digit') == min_digit_guess)) || (data.get('dice_digit') > min_digit_guess) ){
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
                    message.innerText = "";
                    pass_turn(id_game);
                }
            })
        
            .catch(error => console.error("Erreur :", error));
        }else{
            msg.innerText = `Il faut proposer un pari supérieur ! Soit le nombre de dé, soit la valeur des dés doit être supérieure. La valeur des dés ne peut pas baisser !`;
        }
    });

    let liar = document.getElementById('submit_liar');
    liar.addEventListener('click', function(e){
        e.preventDefault();
        let data= new FormData;

        data.append('action', 'liar');
        data.append('id_game', id_game);

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
                if (data.result == "liar"){
                    next_roll(id_game, data.id_liar);
                }
                else if(data.result = "not_liar"){
                    next_roll(id_game, sessionStorage.getItem('session_id'));
                }
            }
        })
        .catch(error => console.error("Erreur :", error));
    })

    let rollie = document.getElementById('rollie');
    rollie.addEventListener('click', ()=>{
        let rules = document.getElementById('rules');
        rules.style.display = 'flex';
        document.getElementById('close_rules').addEventListener('click', ()=>{
            rules.style.display ='none';
        })
    });


    function get_players_info(id){
        let data= new FormData;
        data.append("action", "get_player_game")
        data.append("id_game", id);

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
                let count = 0;
                let idname = "player_";
                data.players.forEach(p =>{
                    count ++;
                    let new_line = document.getElementById(`${idname}${count}`);
                    new_line.innerHTML = '';
                    new_line.classList.remove('notset');
                    let player_box = document.createElement('td');
                    player_box.innerHTML = p.Username;
                    player_box.classList.add('username');
                    if (p.Queued == 0){
                        player_box.classList.add('user_turn');
                    }
                    
                    if (p.ID_player == sessionStorage.getItem('session_id')){
                        player_box.classList.add('you');
                    }

                    if(p.Ranked !== null){
                        player_box.classList.add('eliminated');
                    }
                    
                    new_line.appendChild(player_box);

                    let player_hp = document.createElement('td');
                    let hp_box = document.createElement('div');
                    hp_box.classList.add('player_dices');

                    let i=0;
                    while (i <5){
                        let dice = document.createElement('img');
                        dice.setAttribute('src', '../dice-assets/dice_random.png');
                        if (i >= p.HP){
                            dice.classList.add('no-hp');
                        }
                        hp_box.appendChild(dice);
                        i++
                    }
                    player_hp.appendChild(hp_box);
                    new_line.appendChild(player_hp);
                });
            }
        })
        .catch(error => console.error("Erreur :", error));
        
    }

    function get_current_guess(id){
        let data= new FormData;

        data.append('action', 'get_guess');
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
                let current_guess = document.getElementById('current_guess');
                if (data.pari){
                    let nb_dice = data.guess.nb_dice;
                    let digit_dice = data.guess.digit_dice;

                    min_nb_guess = parseInt(nb_dice);
                    min_digit_guess = parseInt(digit_dice);

                    current_guess.innerHTML = `Pari actuel : Au moins ${nb_dice} dés de ${digit_dice}`;
                }else{
                    current_guess.innerHTML = data.message;
                    min_nb_guess = null;
                    min_digit_guess = null;
                }
            }
        })
        .catch(error => console.error("Erreur :", error));


    }

    function get_turn(id){
        let data= new FormData;

        data.append('action', 'get_turn');
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
                let turn_status = document.getElementById('turn_status');
                turn_status.style.color = '#00BD6B';
                turn_status.innerHTML = data.message;
                if (document.getElementById('dual_turn_status')){
                    let dual_turn_status = document.getElementById('dual_turn_status');
                    dual_turn_status.innerHTML = data.message;
                }

                if (!data.your_turn){
                    document.getElementById('submit_guess').disabled = true;
                    document.getElementById('submit_liar').disabled = true;
                    if (document.getElementById('submit_sum')){
                        document.getElementById('submit_sum').disabled = true;
                    }
                    
                }else {
                    if (document.getElementById('submit_sum')){
                        document.getElementById('submit_sum').disabled = false;
                    }
                    document.getElementById('submit_guess').disabled = false;
                    if (min_digit_guess == null || min_nb_guess == null){
                        document.getElementById('submit_liar').disabled = true;
                    }
                    else{
                        document.getElementById('submit_liar').disabled = false;
                    }
                }
            }
        })
        .catch(error => console.error("Erreur :", error));
    }

    function get_player_dice(id){
        let data = new FormData;

        data.append('action', 'get_player_dices');
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
            console.log(data);
            
            if (data.status == "succes") {
                let dice_list = [data.dice_list.Dice1, data.dice_list.Dice2, data.dice_list.Dice3, data.dice_list.Dice4, data.dice_list.Dice5];
                dice_list.sort(function(a,b) { return a - b; });
                
                let dices_box = document.getElementById('your_dices');
                let count = 0;
                let path_dice = "../dice-assets/"
                while (count < 5){                    
                    if (dice_list[count] != null){
                        let dice;
                        if (!document.getElementById(`Dice_${count}`)){
                            dice = document.createElement('img');
                        }
                        else{
                            dice = document.getElementById(`Dice_${count}`);
                        }

                        dice.setAttribute('src', `${path_dice}dice_${dice_list[count]}.png`);
                        dice.setAttribute('id', `Dice_${count}`);
                        dices_box.appendChild(dice);

                        if (document.getElementById('dual_your_dices')){
                            let dual_dices_box = document.getElementById('dual_your_dices')
                            if (!document.getElementById(`dual_Dice_${count}`)){
                                dual_dice = document.createElement('img');
                            }
                            else{
                                dual_dice = document.getElementById(`dual_Dice_${count}`);
                            }
                            dual_dice.setAttribute('src', `${path_dice}dice_${dice_list[count]}.png`);
                            dual_dice.setAttribute('id', `dual_Dice_${count}`);
                            dual_dices_box.appendChild(dual_dice);

                        }

                        count++;
                    }else{
                        if (document.getElementById('dual_your_dices')){
                            count++;
                            continue;
                        }

                        if (!document.getElementById(`Dice_${count}`)){
                            dice = document.createElement('img');
                        }
                        else{
                            dice = document.getElementById(`Dice_${count}`);
                        }
                        dice.style.display = 'none';
                        count ++;
                        continue;
                    }
                }

            }
        })
        .catch(error => console.error("Erreur :", error));
    }

    function pass_turn(id_game){
        let data= new FormData;

        data.append('action', 'pass_turn');
        data.append('id_game', id_game);

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
                console.log(data.message);
            }
        })
        .catch(error => console.error("Erreur :", error));
        
    }

    function next_roll(id_game, id_lost){
        let data = new FormData;

        data.append('action', 'next_roll');
        data.append('id_lost', id_lost);
        data.append('id_game', id_game);

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
                if (data.endgame){
                    endgame(id_game);
                }
            }
        })
        .catch(error => console.error("Erreur :", error));
    }

    function get_event(id){
        let data= new FormData;

        data.append('action', 'get_last_event');
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
                let event = document.getElementById('last_event');
                if (data.event.last_event){
                    event.innerText = data.event.last_event;
                }

                
            }
        })
        .catch(error => console.error("Erreur :", error));
    }

    function check_endgame(id){
        let data= new FormData;

        data.append('action', 'check_endgame');
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
                if (data.finished[0] != null){
                    show_result(data.players);
                }
                
            }
        })
        .catch(error => console.error("Erreur :", error));
    }

    function endgame(id){
        clearInterval(game);

        let data = new FormData;

        data.append('action', 'endgame');
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
                show_result(data.players);
            }
        })
        .catch(error => console.error("Erreur :", error));
    }

    function show_result(players){
        let result_container;

        if(!document.getElementById('result_container')){
            result_container = document.createElement('div');
        }
        else{
            return;
        }
        
        result_container.setAttribute('id', 'result_container');

        let title = document.createElement('h1');
        title.innerText = 'Résultats de la partie';
        result_container.appendChild(title);

        let result_table = document.createElement('table');
        result_table.setAttribute('id', 'result_table');

        let thead = document.createElement('thead');
        let head_player = document.createElement('th');
        head_player.innerText = "Joueur";

        let head_rank = document.createElement('th');
        head_rank.innerText = "Classement";

        let head_eliar = document.createElement('th');
        head_eliar.innerText = "Eliar gagnés";

        thead.appendChild(head_player);
        thead.appendChild(head_rank);
        thead.appendChild(head_eliar);

        result_table.appendChild(thead);

        players.forEach( p => {
            let new_line = document.createElement('tr');
            let cell_player = document.createElement('td');
            let cell_rank = document.createElement('td');
            let cell_eliar = document.createElement('td');

            cell_player.innerText = p[0];
            cell_rank.innerText = p[1];
            cell_eliar.innerText = ((1 + p[2]) * (p[2] - p[1]));

            new_line.appendChild(cell_player);
            new_line.appendChild(cell_rank);
            new_line.appendChild(cell_eliar);

            result_table.appendChild(new_line);
        });
        result_container.appendChild(result_table);

        let btn_home = document.createElement('button');
        btn_home.setAttribute('id', 'tohome');
        btn_home.innerText = "Acceuil";

        btn_home.addEventListener('click', (e)=>{
            window.location.replace('../pages/home.html');
        })
        result_container.appendChild(btn_home);
        document.body.appendChild(result_container);
    }

    function check_dual(id){
        let data= new FormData;

        data.append('action', 'check_dual');
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
                if(data.dual){
                    let dual_container;
                    if (!document.getElementById('dual_container')){
                        dual_container = document.createElement('dual_container');
                        dual_container.setAttribute('id', 'dual_container');

                        let dual_title = document.createElement('h1');
                        dual_title.innerText = data.message;
                        dual_title.style.color = '#00b7bd';
                        let dual_info = document.createElement('h3');
                        dual_info.innerText = `Il ne reste que 2 joueurs ayant chacun un seul dé, pariez maintenant sur la somme des deux dés restants.
                        En cas d'égalité, vos dés restent inchangés et on recommence jusqu'à ce qu'un vainqueur soit désigné`;

                        let dual_turn = document.createElement('h1');
                        dual_turn.setAttribute('id', 'dual_turn_status');
                        dual_turn.style.display = 'inline';

                        dual_container.appendChild(dual_title);
                        dual_container.appendChild(dual_info);
                        dual_container.appendChild(dual_turn);

                        let your_dices = document.createElement('div');
                        your_dices.setAttribute('id', 'dual_your_dices');
                        let dices_text = document.createElement('h1');
                        dices_text.innerText = 'Votre dé :';

                        your_dices.appendChild(dices_text);
                        dual_container.appendChild(your_dices);

                        let somme_label = document.createElement('label');
                        somme_label.innerText = "Somme à parier :";
                        let somme = document.createElement('input');
                        somme.setAttribute('type', 'number');
                        somme.setAttribute('min', '2');
                        somme.setAttribute('max', '12');
                        somme.setAttribute('id', 'guess_sum');
                        somme.setAttribute('name', 'guess_sum');

                        dual_container.appendChild(somme_label);
                        dual_container.appendChild(somme);
                        
                        let submit_sum = document.createElement('button');
                        submit_sum.setAttribute('id', 'submit_sum');
                        submit_sum.disabled = true;
                        submit_sum.innerText = 'Parier !';
                        dual_container.appendChild(submit_sum);
                        submit_sum.addEventListener('click', (e)=>{
                            let guess_value = somme.value;
                            if(guess_value > 0){
                                dual_time(id, guess_value);
                            }
                        });

                        document.body.appendChild(dual_container);
                    }
                }
                

            }
        })
        .catch(error => console.error("Erreur :", error));
    }

    function dual_time(id, guess){
        let data= new FormData;

        data.append('action', 'dual_time');
        data.append('id_game', id);
        data.append('guess_value', guess);

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
                console.log(data.loser, data.draw);
                if(data.draw){
                    return;
                }
                else if(data.loser){
                    next_roll(id, data.loser);
                }
                 
            }
        })
        .catch(error => console.error("Erreur :", error));
    }


});