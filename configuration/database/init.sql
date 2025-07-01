CREATE TABLE USER
(
    id        INTEGER PRIMARY KEY,
    joined_at TIMESTAMP NOT NULL,

    username      TEXT      UNIQUE NOT NULL,
    password_hash TEXT      NOT NULL
);

CREATE TABLE CHAT
(
    id          INTEGER PRIMARY KEY,
    owner_id    INTEGER   NOT NULL,
    created_at  TIMESTAMP NOT NULL,

    name        TEXT      NOT NULL,

    FOREIGN KEY (owner_id) REFERENCES USER (id)
);

CREATE TABLE CHAT_USER
(
    chat_id   INTEGER   NOT NULL,
    user_id   INTEGER   NOT NULL,

    joined_at TIMESTAMP NOT NULL,

    PRIMARY KEY (chat_id, user_id),
    FOREIGN KEY (chat_id) REFERENCES CHAT (id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES USER (id) ON DELETE CASCADE
);

CREATE TABLE CHAT_MESSAGE
(
    id      INTEGER PRIMARY KEY,
    chat_id INTEGER   NOT NULL,
    user_id INTEGER   NOT NULL,

    message TEXT      NOT NULL,
    sent_at TIMESTAMP NOT NULL,

    FOREIGN KEY (user_id) REFERENCES USER (id) ON DELETE SET NULL,
    FOREIGN KEY (chat_id) REFERENCES CHAT (id) ON DELETE CASCADE
);