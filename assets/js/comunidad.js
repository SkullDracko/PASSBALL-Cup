/**
 * ============================================================
 * PASSBALL Cup - Comunidad
 * ============================================================
 */

document.addEventListener("DOMContentLoaded", function () {

    /* ========================================================
       ELEMENTOS
       ======================================================== */

    const comunidad = document.querySelector("#view-comunidad");

    if (!comunidad) {
        return;
    }

    const postContent =
        comunidad.querySelector("#postContent");

    const publishPost =
        comunidad.querySelector("#publishPost");

    const postMessage =
        comunidad.querySelector("#postMessage");

    const postsList =
        comunidad.querySelector("#postsList");

    const postSort =
        comunidad.querySelector("#postSort");


    /* ========================================================
       MENSAJES
       ======================================================== */

    function showMessage(message, type) {

        if (!postMessage) {
            return;
        }

        postMessage.textContent = message;

        postMessage.className =
            "post-message show " + type;

        setTimeout(function () {

            postMessage.classList.remove("show");

        }, 3000);
    }


    /* ========================================================
       PUBLICAR
       ======================================================== */

    if (publishPost) {

        publishPost.addEventListener("click", function () {

            const text =
                postContent
                    ? postContent.value.trim()
                    : "";

            if (text === "") {

                showMessage(
                    "Escribe algo antes de publicar.",
                    "error"
                );

                if (postContent) {
                    postContent.focus();
                }

                return;
            }


            publishPost.disabled = true;

            publishPost.innerHTML =
                '<i class="fa-solid fa-spinner fa-spin"></i> Publicando...';


            setTimeout(function () {

                const newPost =
                    document.createElement("article");

                newPost.className =
                    "community-post new-post";

                newPost.setAttribute(
                    "data-likes",
                    "0"
                );


                newPost.innerHTML = `

                    <div class="post-header">

                        <div class="post-user-avatar">
                            <i class="fa-solid fa-user"></i>
                        </div>

                        <div class="post-user-info">

                            <strong>
                                Usuario
                            </strong>

                            <span>
                                Hace unos segundos
                                <i class="fa-solid fa-earth-americas"></i>
                            </span>

                        </div>

                        <button
                            type="button"
                            class="post-menu"
                        >
                            <i class="fa-solid fa-ellipsis"></i>
                        </button>

                    </div>


                    <div class="post-body">

                        <p class="post-description">
                            ${escapeHTML(text)}
                        </p>

                    </div>


                    <div class="post-reactions">

                        <div class="reaction-summary">

                            <span class="reaction-icons">

                                <span class="reaction-like">
                                    <i class="fa-solid fa-thumbs-up"></i>
                                </span>

                            </span>

                            <span>
                                Sé el primero en reaccionar
                            </span>

                        </div>

                        <span>
                            0 comentarios
                        </span>

                    </div>


                    <div class="post-buttons">

                        <button
                            type="button"
                            class="post-action like-button"
                        >

                            <i class="fa-regular fa-thumbs-up"></i>

                            <span>
                                Me gusta
                            </span>

                        </button>


                        <button
                            type="button"
                            class="post-action comment-button"
                        >

                            <i class="fa-regular fa-comment"></i>

                            <span>
                                Comentar
                            </span>

                        </button>


                        <button
                            type="button"
                            class="post-action share-button"
                        >

                            <i class="fa-solid fa-share"></i>

                            <span>
                                Compartir
                            </span>

                        </button>

                    </div>


                    <div class="comments-area">

                        <input
                            type="text"
                            class="comment-input"
                            placeholder="Escribe un comentario..."
                        >

                    </div>

                `;


                if (postsList) {

                    postsList.prepend(newPost);

                }


                postContent.value = "";

                publishPost.disabled = false;

                publishPost.innerHTML =
                    'Publicar <i class="fa-solid fa-arrow-right"></i>';


                showMessage(
                    "¡Publicación creada correctamente!",
                    "success"
                );


                initializePost(newPost);

            }, 700);

        });

    }


    /* ========================================================
       BOTONES DE TIPO DE PUBLICACIÓN
       ======================================================== */

    const postTypeButtons =
        comunidad.querySelectorAll(
            ".post-type-button"
        );

    postTypeButtons.forEach(function (button) {

        button.addEventListener(
            "click",
            function () {

                const type =
                    this.getAttribute("data-type");

                const messages = {

                    imagen:
                        "Aquí podrás adjuntar una imagen.",

                    video:
                        "Aquí podrás adjuntar un video.",

                    encuesta:
                        "Aquí podrás crear una encuesta.",

                    evento:
                        "Aquí podrás crear un evento."

                };

                showMessage(
                    messages[type] ||
                    "Función seleccionada.",
                    "success"
                );

            }
        );

    });


    /* ========================================================
       INICIALIZAR PUBLICACIONES
       ======================================================== */

    const existingPosts =
        comunidad.querySelectorAll(
            ".community-post"
        );

    existingPosts.forEach(function (post) {

        initializePost(post);

    });


    /* ========================================================
       FUNCIONES DE PUBLICACIÓN
       ======================================================== */

    function initializePost(post) {

        if (!post) {
            return;
        }


        /* ====================================================
           ME GUSTA
           ==================================================== */

        const likeButton =
            post.querySelector(".like-button");

        if (likeButton) {

            likeButton.addEventListener(
                "click",
                function () {

                    const icon =
                        this.querySelector("i");

                    const liked =
                        this.classList.toggle("liked");


                    if (liked) {

                        icon.className =
                            "fa-solid fa-thumbs-up";

                        this.querySelector("span")
                            .textContent =
                            "Te gusta";

                    } else {

                        icon.className =
                            "fa-regular fa-thumbs-up";

                        this.querySelector("span")
                            .textContent =
                            "Me gusta";

                    }

                }
            );

        }


        /* ====================================================
           COMENTARIOS
           ==================================================== */

        const commentButton =
            post.querySelector(".comment-button");

        const commentsArea =
            post.querySelector(".comments-area");

        if (
            commentButton &&
            commentsArea
        ) {

            commentButton.addEventListener(
                "click",
                function () {

                    commentsArea.classList.toggle(
                        "show"
                    );

                    if (
                        commentsArea.classList.contains(
                            "show"
                        )
                    ) {

                        const input =
                            commentsArea.querySelector(
                                ".comment-input"
                            );

                        if (input) {
                            input.focus();
                        }

                    }

                }
            );

        }


        /* ====================================================
           COMPARTIR
           ==================================================== */

        const shareButton =
            post.querySelector(".share-button");

        if (shareButton) {

            shareButton.addEventListener(
                "click",
                function () {

                    if (
                        navigator.clipboard &&
                        window.location.href
                    ) {

                        navigator.clipboard
                            .writeText(
                                window.location.href
                            )
                            .then(function () {

                                showMessage(
                                    "Enlace copiado para compartir.",
                                    "success"
                                );

                            })
                            .catch(function () {

                                showMessage(
                                    "Publicación lista para compartir.",
                                    "success"
                                );

                            });

                    } else {

                        showMessage(
                            "Publicación lista para compartir.",
                            "success"
                        );

                    }

                }
            );

        }


        /* ====================================================
           COMENTAR CON ENTER
           ==================================================== */

        const commentInput =
            post.querySelector(".comment-input");

        if (commentInput) {

            commentInput.addEventListener(
                "keydown",
                function (event) {

                    if (
                        event.key === "Enter" &&
                        this.value.trim() !== ""
                    ) {

                        event.preventDefault();

                        showMessage(
                            "Comentario agregado correctamente.",
                            "success"
                        );

                        this.value = "";

                    }

                }
            );

        }


        /* ====================================================
           MENÚ
           ==================================================== */

        const menuButton =
            post.querySelector(".post-menu");

        if (menuButton) {

            menuButton.addEventListener(
                "click",
                function () {

                    showMessage(
                        "Opciones de publicación.",
                        "success"
                    );

                }
            );

        }

    }


    /* ========================================================
       ORDENAR PUBLICACIONES
       ======================================================== */

    if (postSort && postsList) {

        postSort.addEventListener(
            "change",
            function () {

                const posts =
                    Array.from(
                        postsList.querySelectorAll(
                            ".community-post"
                        )
                    );


                if (this.value === "popular") {

                    posts.sort(function (a, b) {

                        return (
                            Number(
                                b.dataset.likes || 0
                            ) -
                            Number(
                                a.dataset.likes || 0
                            )
                        );

                    });

                } else {

                    posts.sort(function (a, b) {

                        return (
                            a.classList.contains(
                                "new-post"
                            )
                                ? -1
                                : 1
                        );

                    });

                }


                posts.forEach(function (post) {

                    postsList.appendChild(post);

                });

            }
        );

    }


    /* ========================================================
       ESCAPAR HTML
       ======================================================== */

    function escapeHTML(value) {

        const div =
            document.createElement("div");

        div.textContent = value;

        return div.innerHTML;

    }

});
