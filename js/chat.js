document.addEventListener("DOMContentLoaded", () => {

    const chatbox = document.getElementById("chatbox");
    const input = document.getElementById("userInput");
    const sendBtn = document.getElementById("sendBtn");

    // 🔥 Send intro only ONCE per bot
    if (!sessionStorage.getItem("intro_sent")) {

        sessionStorage.setItem("intro_sent", "1");

        fetch("ai.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ message: "__intro__" })
        })
        .then(res => res.json())
        .then(data => {

            if (data.reply) {
                addAIMessage(data.reply);
            }

        })
        .catch(() => {
            addAIMessage("I'm here 💜 Something went wrong.");
        });
    }

    sendBtn.addEventListener("click", sendMessage);

    input.addEventListener("keypress", (e) => {
        if (e.key === "Enter") sendMessage();
    });

    function sendMessage() {

        const text = input.value.trim();
        if (!text) return;

        addUserMessage(text);
        input.value = "";

        // Example character and systemPrompt (replace with actual values as needed)
        const character = {
            id: "char1",
            name: "Aira",
            personality: "gentle, empathetic, supportive",
            tagline: "Always here to listen."
        };
        const systemPrompt = `You are ${character.name}. ${character.personality}...`;

        // Retrieve last 10 messages from chatbox for conversationHistory
        const messages = Array.from(chatbox.querySelectorAll('.message'));
        const conversationHistory = messages.slice(-10).map(div => {
            const isUser = div.classList.contains('user');
            return {
                role: isUser ? 'user' : 'ai',
                content: div.textContent.trim()
            };
        });

        fetch("ai.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                message: text,
                character,
                systemPrompt,
                conversationHistory
            })
        })
        .then(res => res.json())
        .then(data => {

            if (data.reply) {
                addAIMessage(data.reply);
            }

        })
        .catch(() => {
            addAIMessage("I'm here 💜 Something went wrong.");
        });
    }

    function addUserMessage(text) {

        const div = document.createElement("div");
        div.className = "message user";
        div.innerHTML = `<p>${text}</p>`;

        chatbox.appendChild(div);
        chatbox.scrollTop = chatbox.scrollHeight;
    }

    function addAIMessage(text) {

        const div = document.createElement("div");
        div.className = "message ai";
        div.innerHTML = `<p>${text}</p>`;

        chatbox.appendChild(div);
        chatbox.scrollTop = chatbox.scrollHeight;
    }

});
