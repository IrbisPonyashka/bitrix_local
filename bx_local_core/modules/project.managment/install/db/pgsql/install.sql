create table if not exists b_project_params (
     ID int not null auto_increment,
     PROJECT_ID int not null,
     ESTIMATED_TIME varchar(5) not null,
     CUSTOMER_ID int not null,
     CUSTOMER_TYPE varchar(7) not null,
    primary key (ID)
);