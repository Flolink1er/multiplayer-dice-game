window.addEventListener("load", () => {
    const id_game = new URLSearchParams(window.location.search).get('id_game');

    setInterval(function(){
        get_msg_game(id_game)
    }, 1000);


    function get_msg_game(id_game){
        let chatbox = document.getElementById('chat_box');
        let data =  new FormData;
        data.append("action", "get_msg");
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
    
            if (data.status == "succes") {
                
                data.msg_list.forEach(msg => {
                    if(document.getElementById(`msg_${msg.ID_Message}`) != undefined){
                        return;
                    }
                    else{
                        let msg_line = document.createElement('div');
                        let player = document.createElement('div');
                        let msg_box = document.createElement('div');

                        msg_line.setAttribute('id', `msg_${msg.ID_Message}`);

                        msg_line.classList.add('msg_box')
                        player.classList.add('player_name');
                        msg_box.classList.add('message');
                        
                        if (msg.ID_player == sessionStorage.getItem("session_id")){
                            player.classList.add("your_msg");
                        }

                        player.innerHTML = msg.Username;
                        msg_box.innerHTML = msg.content;

                        msg_line.appendChild(player);
                        msg_line.appendChild(msg_box);

                        chatbox.appendChild(msg_line);
                        chatbox.scrollTop = chatbox.scrollHeight;
                    }
                });
            }
            else if(data.status == "nothing"){
                chatbox.innerHTML = data.message;
            }
            else {
                console.log(data.message);
            }
        })
        .catch(error => console.error("Erreur :", error));
    }

    let msg = document.getElementById('send_msg');
    msg.addEventListener('submit', (e)=>{
        e.preventDefault();
        msg_content = document.getElementById('msg').value;
        let data = new FormData();
        
        data.append("msg", msg_content);
        data.append("action", "send_msg");
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

            .then(data=>{
                document.getElementById('msg').value = "";
            })
            .catch(error => console.error("Erreur :", error));
    })
})