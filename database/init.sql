-- Borramos todo si ya existía, para poder volver a ejecutar el script
DROP TABLE IF EXISTS "productos";
DROP TABLE IF EXISTS "user_roles";
DROP TABLE IF EXISTS "usuarios";
DROP TABLE IF EXISTS "categorias";
DROP SEQUENCE IF EXISTS productos_id_seq;
DROP SEQUENCE IF EXISTS usuarios_id_seq;

-- ===================== CATEGORIAS =====================
CREATE TABLE "public"."categorias"
(
    "is_deleted" boolean DEFAULT false,
    "created_at" timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
    "updated_at" timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
    "id" uuid NOT NULL,
    "nombre" character varying(255) NOT NULL,
    CONSTRAINT "categorias_nombre_key" UNIQUE ("nombre"),
    CONSTRAINT "categorias_pkey" PRIMARY KEY ("id")
);

INSERT INTO "categorias" ("id", "nombre")
VALUES ('19135792-b778-441f-8786-8a90d0c2d1a1', 'DEPORTES'),
       ('662ed342-de99-45c6-8498-5a8a0e1c7b02', 'COMIDA'),
       ('b79182ad-91c3-46e8-9055-1b3a7f9e4c03', 'BEBIDA'),
       ('4fa72b3f-dca2-4fd8-b8a1-2c7d6e5f8a04', 'COMPLEMENTOS'),
       ('1e2584d8-db52-45da-b2c3-9d8e7f6a5b05', 'OTROS');

-- ===================== PRODUCTOS =====================
CREATE SEQUENCE productos_id_seq INCREMENT 1 MINVALUE 1 START 1;

CREATE TABLE "public"."productos"
(
    "is_deleted" boolean DEFAULT false,
    "precio" double precision DEFAULT '0.0',
    "stock" integer DEFAULT '0',
    "created_at" timestamp DEFAULT CURRENT_TIMESTAMP,
    "id" bigint DEFAULT nextval('productos_id_seq') NOT NULL,
    "updated_at" timestamp DEFAULT CURRENT_TIMESTAMP,
    "categoria_id" uuid,
    "uuid" uuid,
    "descripcion" character varying(255),
    "imagen" text DEFAULT 'https://via.placeholder.com/150',
    "marca" character varying(255),
    "modelo" character varying(255),
    CONSTRAINT "productos_pkey" PRIMARY KEY ("id"),
    CONSTRAINT "productos_uuid_key" UNIQUE ("uuid")
);

-- No ponemos el "id": lo asigna la secuencia automaticamente
INSERT INTO "productos" ("precio", "stock", "categoria_id", "uuid", "descripcion", "imagen", "marca", "modelo")
VALUES (249.95, 6, '19135792-b778-441f-8786-8a90d0c2d1a1', 'd69cf3db-b77d-4181-b3cd-5ca8107fb6a9', 'Pala de pádel de potencia, forma diamante', 'https://via.placeholder.com/150', 'Bullpadel', 'Vertex 04'),
       (189.90, 4, '19135792-b778-441f-8786-8a90d0c2d1a1', '6dbcbf5e-8e1c-47cc-8578-7b0a33ebc154', 'Pala de pádel de control, forma redonda', 'https://via.placeholder.com/150', 'Nox', 'AT10 Genius'),
       (89.99, 12, '19135792-b778-441f-8786-8a90d0c2d1a1', 'a3c1f2d4-5b6e-4f70-8a91-0b2c3d4e5f61', 'Zapatillas de pádel con suela de espiga', 'https://via.placeholder.com/150', 'Adidas', 'Adizero Ubersonic'),
       (5.99, 40, '4fa72b3f-dca2-4fd8-b8a1-2c7d6e5f8a04', 'b4d2e3f5-6c7f-4a81-9ba2-1c3d4e5f6a72', 'Pack de 3 overgrips para pala', 'https://via.placeholder.com/150', 'Head', 'Xtreme Soft'),
       (59.90, 8, '4fa72b3f-dca2-4fd8-b8a1-2c7d6e5f8a04', 'c5e3f4a6-7d80-4b92-8cb3-2d4e5f6a7b83', 'Paletero con compartimento térmico para 3 palas', 'https://via.placeholder.com/150', 'Babolat', 'RH Padel Pro'),
       (4.50, 25, 'b79182ad-91c3-46e8-9055-1b3a7f9e4c03', 'd6f4a5b7-8e91-4ca3-9dc4-3e5f6a7b8c94', 'Bebida isotónica sabor limón, botella 750 ml', 'https://via.placeholder.com/150', 'Aquarius', 'Limón 750ml');

-- ===================== USUARIOS =====================
CREATE SEQUENCE usuarios_id_seq INCREMENT 1 MINVALUE 1 START 1;

CREATE TABLE "public"."usuarios"
(
    "is_deleted" boolean DEFAULT false,
    "created_at" timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
    "id" bigint DEFAULT nextval('usuarios_id_seq') NOT NULL,
    "updated_at" timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL,
    "apellidos" character varying(255) NOT NULL,
    "email" character varying(255) NOT NULL,
    "nombre" character varying(255) NOT NULL,
    "password" character varying(255) NOT NULL,
    "username" character varying(255) NOT NULL,
    CONSTRAINT "usuarios_email_key" UNIQUE ("email"),
    CONSTRAINT "usuarios_pkey" PRIMARY KEY ("id"),
    CONSTRAINT "usuarios_username_key" UNIQUE ("username")
);

-- Contraseña: admin Admin1
-- Contraseña: user User1234
-- Contraseña: test test1234
INSERT INTO "usuarios" ("apellidos", "email", "nombre", "password", "username")
VALUES ('Admin Admin', 'admin@prueba.net', 'Admin', '$2y$10$6wgbcN4nV7YB1zTGeCwZL.uLkxaqX.zSFT2J.BRIwPOoBPFQRq2a2', 'admin'),
       ('User User', 'user@prueba.net', 'User', '$2y$10$G.bg84m2xh1U9GTD/APvnuTxCLHihi.MLvEV37qds2/IcWzmiJK3i', 'user'),
       ('Test Test', 'test@prueba.net', 'Test', '$2y$10$mXi9A4bYakxvCnOK1XiEKO3.muyyVdnYAT.1l9pLaXZjWFh5N3Tdi', 'test');

-- ===================== ROLES =====================
CREATE TABLE "public"."user_roles"
(
    "user_id" bigint NOT NULL,
    "roles" character varying(255)
);

INSERT INTO "user_roles" ("user_id", "roles")
VALUES (1, 'USER'),
       (1, 'ADMIN'),
       (2, 'USER'),
       (3, 'USER');

-- ===================== CLAVES FORANEAS =====================
ALTER TABLE ONLY "public"."productos"
    ADD CONSTRAINT "fk_productos_categoria" FOREIGN KEY (categoria_id) REFERENCES categorias (id);

ALTER TABLE ONLY "public"."user_roles"
    ADD CONSTRAINT "fk_roles_usuario" FOREIGN KEY (user_id) REFERENCES usuarios (id);