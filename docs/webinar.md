# Webinar management

Group and public conversations can be used to host webinars. Those online meetings can have a lobby, which come with the following restrictions:

* Only moderators can start/join a call
* Only moderators can read and write chat messages
* Normal users can only join the room. They then pull the room endpoint regularly for an update and should start the chat and signaling as well as allowing to join the call, once the lobby got disabled.


Base endpoint is: `/ocs/v2.php/apps/spreed/api/v1`

## Set lobby for a conversation

* Required capability: `webinary-lobby`
* Method: `PUT`
* Endpoint: `/room/{token}/webinar/lobby`
* Data:

    field | type | Description
    ------|------|------------
    `state` | int | New state for the conversation
    `timer` | int/null | Timestamp when the lobby state is reset to no lobby

* Response:
    - Status code:
        + `200 OK`
        + `400 Bad Request` When the conversation type does not support lobby (only group and public conversation atm)
        + `400 Bad Request` When the given timestamp is invalid
        + `403 Forbidden` When the current user is not a moderator/owner
        + `404 Not Found` When the conversation could not be found for the participant

## Enabled or disable SIP dial-in

* Required capability: `sip-support`
* Method: `PUT`
* Endpoint: `/room/{token}/webinar/sip`
* Data:

    field | type | Description
    ------|------|------------
    `state` | int | New SIP state for the conversation (0 = disabled, 1 = enabled)

* Response:
    - Status code:
        + `200 OK`
        + `400 Bad Request` When the state was invalid or the same
        + `401 Unauthorized` When the user can not enabled SIP
        + `403 Forbidden` When the current user is not a moderator/owner
        + `404 Not Found` When the conversation could not be found for the participant
        + `412 Precondition Failed` When SIP is not configured on the server
