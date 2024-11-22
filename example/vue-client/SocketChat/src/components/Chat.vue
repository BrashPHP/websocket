<template>
  <div class="chat-container">

    <!-- Name Input window -->
    <div v-if="!connect">
      <div class="modal-background">
        <div class="modal-content">
          <form @submit.prevent="handleConnect">
            <h3> Enter your name to join chat </h3>
            <input type="text" v-model="username" placeholder="Enter your name" />
            <br>
            <button type="submit"> Connect </button>
          </form>
        </div>
      </div>
    </div>

    <div v-if="connect" class="chat-window">
      <div class="messages-container">
        <!-- <ul> -->
        <!-- Use a v-for directive to iterate over the messages array and display each message -->
        <div v-for="(val, index) in messages" :key="index">
          <div class="bubble" :class="[val.username !== username ? 'left-bubble' : 'right-bubble']">

            <!-- Use mustache syntax to interpolate the username and message properties of each message object -->
            <div class="chat__conversation-board__message-container" :class="{ reversed: val.username !== username }">
              <div class="chat__conversation-board__message__person"
                :class="{ 'reversed-avatar': val.username !== username }">
                <div class="chat__conversation-board__message__person__avatar">
                  <img :src="val.username !== username ? avatarImage : avatarImage2" alt="Dennis Mikle" />
                </div>
                <span class="chat__conversation-board__message__person__nickname"
                  style="font-weight: bolder; font-size: large;">{{ val.username }}</span>
              </div>



              <div class="chat__conversation-board__message__options">
                <button class="btn-icon chat__conversation-board__message__option-button option-item emoji-button">
                  <svg class="feather feather-smile sc-dnqmqq jxshSx" xmlns="http://www.w3.org/2000/svg" width="24"
                    height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                    <line x1="9" y1="9" x2="9.01" y2="9"></line>
                    <line x1="15" y1="9" x2="15.01" y2="9"></line>
                  </svg>
                </button>
                <button class="btn-icon chat__conversation-board__message__option-button option-item more-button">
                  <svg class="feather feather-more-horizontal sc-dnqmqq jxshSx" xmlns="http://www.w3.org/2000/svg"
                    width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="1"></circle>
                    <circle cx="19" cy="12" r="1"></circle>
                    <circle cx="5" cy="12" r="1"></circle>
                  </svg>
                </button>
              </div>
            </div>
            <div style="margin-top: 2rem;" :class="{ 'text-reversed': val.username !== username }">
              {{ val.message }}
            </div>
          </div>
        </div>
        <!-- </ul> -->
      </div>

      <div class="chat-input">
        <form @submit.prevent="sendMessage()">
          <input type="text" v-model="text" placeholder="Write message..." />
          <button type="submit"><i class="bi bi-send "></i> </button>
        </form>
      </div>
    </div>
  </div>
</template>

<!-- Use the 'setup' function from the Vue Composition API -->
<script>
import { ref } from 'vue';

export default {
  name: 'ChatComponent',
  setup() {
    // Declare reactive variables using the 'ref' function
    const username = ref(null)
    const connect = ref(false)
    const text = ref(null)
    const messages = ref([])
    const imageIcons = [
      "https://i.scdn.co/image/fe839f5bd121774e64f377b5eead237c69a01711",
      "https://www.mefeater.com/wp-content/uploads/2021/11/02-Tyler-the-Creator-bet-awards-red-carpet-2021-billboard-1548-1624836802-compressed.jpg",
      "https://i.pinimg.com/736x/e2/af/9d/e2af9d082a7416487c939c57e3506066.jpg",
      "https://www.rollingstone.com/wp-content/uploads/2021/06/Tyler-by-Luis-Panch-Perez.jpg",
      "https://media.pitchfork.com/photos/6454f9ce60430f8f9be0fb09/master/pass/Tyler-the-Creator-Igor.jpg"]

    const avatarImage = ref(imageIcons[Math.floor(Math.random() * imageIcons.length)]);
    const removedClientImages = imageIcons.filter(el => el !== avatarImage.value)
    const avatarImage2 = ref(removedClientImages[Math.floor(Math.random() * removedClientImages.length)]);
    // The use of the WebSocket API 
    const socket = new WebSocket('ws://192.168.15.167:1337')


    const handleConnect = () => {
      if (username.value.length > 0) {
        connect.value = true
      }
    }

    const sendMessage = () => {
      const messageData = { username: username.value, message: text.value };
      // Send the message data to the server using WebSockets
      socket.send(JSON.stringify(messageData))
      // Add the message data to the messages array
      messages.value.push(messageData)
      // Clear the text input
      text.value = null;
    }

    // Define a WebSocket 'onmessage' event handler to receive messages from the server
    socket.onmessage = (event) => {
      const message = JSON.parse(event.data);
      messages.value.push(message);
    }

    // Return the reactive variables and event handlers to be used in the template
    return {
      text,
      connect,
      username,
      messages,
      handleConnect,
      sendMessage,
      avatarImage,
      avatarImage2
    }
  },
}
</script>

<style scoped>
.chat-container {
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}
.messages-container{
  width: 80vw;
  min-width: 300px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
}
.modal-background {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-content {
  background-color: #3d2aac;
  padding: 15px;
  border-radius: 5px;
  box-shadow: 0px 0px 10px #333;
  text-align: center;
  color: white;
}

.modal-content input {
  padding: 10px;
  border-radius: 5px;
  border: none;
  margin-bottom: 10px;
}

.modal-content button {
  padding: 10px;
  border-radius: 5px;
  border: none;
  background-color: #9b59b6;
  color: white;
}

.chat-window {
  display: flex;
  flex-direction: column;
}

.chat-messages {
  padding: 10px;
  margin-bottom: 10px;
  border-radius: 5px;
}

.chat-input {
  background-color: #f2f2f2;
  padding: 30px;
  display: flex;
  align-items: center;
  position: fixed;
  bottom: 0;
  width: 100%;
}

.text-reversed {
  text-align: end;
  margin-right: 0.5rem;
}

.reversed-avatar {
  flex-direction: row-reverse;
}

.reversed-avatar .chat__conversation-board__message__person__avatar {
  margin-left: 0.5rem;
}

.chat-input input[type="text"] {
  padding: 15px;
  border: none;
  border-radius: 5px 0px 0px 5px;
}

.chat-input button {
  background-color: #3d2aac;
  color: #fff;
  padding: 15px;
  border: none;
  border-radius: 0px 5px 5px 0px;
}

.message-container {
  display: inline-block;
  padding: 0 0 0 10px;
  vertical-align: top;
}


.bubble {
  --gap: 2px;
  --width: 800px;
  --background: #333;
  background-color: lightgray;
  border-radius: 16px;
  padding: 8px 16px;
  box-shadow: 0 0 0 var(--gap) var(--background);
  display: block;
}

.bubble>.chat__conversation-board__message-container {
  width: 30vw;
  min-width: 300px;
}

.right-bubble {
  background: #f6f6f6;
  /* W3C, IE 10+/ Edge, Firefox 16+, Chrome 26+, Opera 12+, Safari 7+ */
  padding: 10px 15px;
  word-wrap: break-word;
  margin: 10px;
  float: right;
  color: #333;
}

.left-bubble {
  text-align: left;
  background: #41295a;
  /* fallback for old browsers */
  background: -webkit-linear-gradient(to right, #2F0743, #41295a);
  /* Chrome 10-25, Safari 5.1-6 */
  background: linear-gradient(to right, #2F0743, #41295a);
  /* W3C, IE 10+/ Edge, Firefox 16+, Chrome 26+, Opera 12+, Safari 7+ */
  padding: 10px 15px;
  word-wrap: break-word;
  margin: 10px;
  float: left;
  color: white;
}

.chat__conversation-board__message-container.reversed {
  flex-direction: row-reverse;
}

.chat__conversation-board__message-container.reversed .chat__conversation-board__message__person {
  margin: 0 0 0 1.2em;
}

.chat__conversation-board__message-container {
  position: relative;
  display: flex;
  flex-direction: row;
  justify-content: space-between;
}

.chat__conversation-board__message-container:not(:last-child) {
  margin: 0 0 2em 0;
}

.chat__conversation-board__message__person {
  text-align: center;
  margin: 0 1.2em 0 0;
  display: flex;
  align-items: center;
}

.chat__conversation-board__message__person__avatar img {
  height: 100%;
  width: auto;
}

.chat__conversation-board__message__person__avatar {
  height: 45px;
  width: 45px;
  overflow: hidden;
  border-radius: 50%;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
  position: relative;
  margin-right: 0.5rem;
}

.chat__conversation-board__message__person__avatar::before {
  content: "";
  position: absolute;
  height: 100%;
  width: 100%;
}
</style>