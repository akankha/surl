create table urls
(
    id           int auto_increment
        primary key,
    original_url varchar(2048) not null,
    short_code   varchar(64)   not null,
    constraint short_code
        unique (short_code)
);

create table url_clicks
(
    id         int auto_increment
        primary key,
    url_id     int                                not null,
    clicked_at datetime default CURRENT_TIMESTAMP null,
    clicker_ip varchar(45)                        not null,
    constraint url_clicks_ibfk_1
        foreign key (url_id) references urls (id)
);

create index url_id
    on url_clicks (url_id);

