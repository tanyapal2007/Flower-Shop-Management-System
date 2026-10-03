/* =========================================================
   GLOBAL CUSTOM POPUP
========================================================= */

function showPopup(
    message,
    type = "success",
    title = "",
    redirect = null
) {

    /* Remove existing popup */

    const oldPopup =
        document.getElementById("globalCustomPopup");

    if (oldPopup) {
        oldPopup.remove();
    }


    /* Default titles */

    if (title === "") {

        if (type === "success") {
            title = "Success";
        }

        else if (type === "error") {
            title = "Error";
        }

        else if (type === "warning") {
            title = "Warning";
        }

        else {
            title = "Information";
        }
    }


    /* Icons */

    let icon = "";

    if (type === "success") {

        icon =
            '<i class="fa fa-check"></i>';

    }

    else if (type === "error") {

        icon =
            '<i class="fa fa-times"></i>';

    }

    else if (type === "warning") {

        icon =
            '<i class="fa fa-exclamation"></i>';

    }

    else {

        icon =
            '<i class="fa fa-info"></i>';
    }


    /* Create popup */

    const popup =
        document.createElement("div");


    popup.id =
        "globalCustomPopup";


    popup.className =
        "custom-popup-overlay";


    popup.innerHTML = `

        <div class="custom-popup-box custom-popup-${type}">

            <div class="custom-popup-icon">
                ${icon}
            </div>

            <div class="custom-popup-title">
                ${title}
            </div>

            <p class="custom-popup-message">
                ${message}
            </p>

            <div class="custom-popup-progress">

                <div class="custom-popup-progress-bar"></div>

            </div>

        </div>

    `;


    document.body.appendChild(popup);


    /* Show popup */

    setTimeout(function () {

        popup.classList.add("show");

    }, 10);


    /* Hide after 2 seconds */

    setTimeout(function () {

        popup.classList.remove("show");


        setTimeout(function () {

            popup.remove();


            /* Redirect */

            if (redirect) {

                window.location.href =
                    redirect;

            }

        }, 250);

    }, 2000);

}