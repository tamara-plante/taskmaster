$(function() 
{
    // Add scroll event listener
    $(".scrollbar").on("scroll", function(event) {
        $(".scroll-indicator").css("visibility", ($(event.target).scrollTop() == 0) ? "visible" : "hidden");
    });

    // Submit priority on click
    $(".form-btn").on("click", submitOptionForm);

    // Show add task form
    $("#task-add-btn").on("click", toggleAddTask);

    // Reroute to selected list
    $(".list-item .list-title").on("click", route);

    // Update backend/frontend

    // Submit the title on focus out.
    $("#list-title-form input").on("focusout", updateTitle);
    // When the input changes, reset the custom validity.
    $("#list-title-form input").on("input", function(event) {
        const inputDOM = $("#list-title-form input").get(0);
        inputDOM.setCustomValidity("");
        inputDOM.reportValidity();
    });
    // Prevent default submit.
    $("#list-title-form").on("submit", function(event) {
        $("#list-title-form input").blur(); // Force submit
        event.preventDefault();
    });

    // Delete list
    $(".list-delete").on("click", deleteList);

    // Submit add task form
    $("#task-add-form").on("submit", addOrUpdateTask);
    // Toggle task
    $("#tasks-container ul").on("click", "li", toggleTask);
    // Edit task
    $("#tasks-container ul").on("click", "li .task-edit", editTask);
    // Delete task
    $("#tasks-container ul").on("click", "li .task-delete", deleteTask);

})

function toggleAddTask(event)
{
    $("#task-add-form").toggle(200);
    $("#task-add-btn").toggleClass("hide");

    // Showing the dialog and hiding the default button
    if ($("#task-add-btn").hasClass("hide")) {
        $("#task-add-form textarea").focus();
    }
    // Hiding the dialog
    else {
        $("#task-add-form").removeClass("edit");
        $("#task-add-form").trigger("reset");
    }
}

function route(event)
{
    const target = $(event.currentTarget);
    let url = target.data("url");

    if (typeof(url) === "undefined") {
        url = target.closest("li").data("url");
    }

    window.location.href = url;
}

/**
 * Submit form without submit buttons.
 * @param {string} formId the form id to submit
 */
function submitForm(formId)
{
    document.getElementById(formId).submit();
}

/**
 * Submit form without submit button.
 * @param {event} event 
 */
function submitOptionForm(event)
{
    const button = $(event.currentTarget);
    const form = button.closest("form");

    form.submit();
}


/**
 * Update the selected list title.
 * @param {event} event 
 */
function updateTitle(event)
{
    event.preventDefault();

    const form = $("#list-title-form");
    const listId = $("#list-title-form").data("id");
    const input = $("#list-title-form input[name=title]");
    const inputDOM = input.get(0);

    $.post(`${form.attr("action")}`, {"title": input.val()})
        .done(function(data) {
            dataTitle = JSON.parse(data);

            if ("error" in dataTitle) {
                // Solution here
                // https://stackoverflow.com/questions/30958536/custom-validity-jquery
                inputDOM.setCustomValidity(dataTitle.error);
                inputDOM.reportValidity();
            }
            // Set up the new title and in the list
            else  {
                const newTitle = dataTitle.title;
                input.val(newTitle);
                $(`li[data-id="${listId}"] .list-title`).text(newTitle);
            }
        })
}

/**
 * Create the task through json response 
 * to practice a different approach to improve ux.
 * @param {event} event 
 */
function addOrUpdateTask(event)
{
    event.preventDefault();

    const form = event.currentTarget;
    const formData = new FormData(form);
    const isUpdate = $(form).hasClass("edit");

    $.ajax({
        url: `${form.action}${isUpdate ? "/" + formData.get("id") : ""}`,
        type: "POST",
        data: formData,
        // Make formData work w jquery
        contentType: false,
        processData: false,
        
        success: function(taskData) {
            if (taskData !== "{}") {
                const task = JSON.parse(taskData);

                // Edit the task
                if (isUpdate) {
                    updateTask(task);
                }
                // Create a new task and trigger hash location.
                else {
                    const li = createTask(task);
                    $("#tasks-container ul").append(li);

                    // Update the count indicator
                    updateActiveTaskCount(1);
                    // Force scroll to the new task;
                    window.location.hash = "t-" + task.id;
                }

                // Hide the create/edit task dialog
                toggleAddTask();
            }
        }
    })
}


/**
 * Toggle the task's completed status
 * @param {event} event 
 */
function toggleTask(event)
{
    const target = $(event.currentTarget);
    const li = target.closest("li");

    $.get(`${li.data("url")}/toggle`)
        .done(function(isCompleted) {
            if (isCompleted) {
                // Update elements
                for (let name of ["task-content", "priority", "checkmark", "task-date", "task-edit"]) {
                    li.find('.' + name).toggleClass("completed");
                }
                // Update the count indicator
                const countValue = ($(li).find(".checkmark").hasClass("completed")) ? -1 : 1;
                updateActiveTaskCount(countValue);
            }
    });
}

/**
 * Update a task information.
 * @param {event} event 
 */
function editTask(event)
{
    event.stopPropagation();

    // Change the add icon
    $("#task-add-form").addClass("edit");

    const task = $(event.currentTarget).closest("li");

    // Get the entry
    $.get(task.data("url"))
        .done(function(taskData) {
            // Fill the information into the form
            if (taskData !== "{}") {
                const task = JSON.parse(taskData);

                // Hidden input id
                $(`#task-add-form input[name="id"]`).val(task.id);
                // Fill in existing task data
                $(`#task-add-form select[name=priority]`).val(task.priority);
                $("#task-add-form textarea[name=content]").val(task.content);
                $("#task-add-form input[type=date]").val(task.due_date);

                toggleAddTask();
            }
    })
}


/**
 * Delete a task
 * @param {event} event 
 */
function deleteTask(event)
{
    event.stopPropagation();

    const target = $(event.currentTarget);
    const li = target.closest("li");

    $.ajax({
        url: li.data("url"),
        type: "DELETE",
        success: function(isCompleted) {
            if (isCompleted) {
                // If the task is not completed
                if (!$(li).find(".checkmark").hasClass("completed")) {
                    updateActiveTaskCount(-1);
                }

                li.remove();
            }
        }
    });
}

/**
 * Delete a list.
 * @param {event} event 
 */
function deleteList(event)
{
    event.stopPropagation();

    const target = $(event.currentTarget);
    const li = target.closest("li");
    const title = $(`li[data-id="${li.data("id")}"] .list-title`).text();

    isConfirm = confirm("Are you sure you want to delete " + title + "?\nThis action cannot be undone.")

    if (isConfirm) {
        $.ajax({
            url: li.data("url"),
            type: "DELETE",
            success: function(isCompleted) {
                if (isCompleted) {
                    /* We need to load a new list*/
                    if (li.hasClass("active")) {
                        window.location.href = li.data("url");
                    }
    
                    $(`li[data-id="${li.data("id")}"]`).remove();
                    //li.remove();
                }
            }
        })
    }
}


/**
 * Create the task element.
 * @param {json} jsonData the json object containing the task data
 * @return {HTMLElement} li the created task li
 */
function createTask(jsonData)
{
    const task = jsonData;
    const taskCompleted = task.is_completed ? "completed" : "";
    // set falsey values to an empty string
    task.due_date = task.due_date || ""; 

    // Task li
    const li = $(`<li id="t-${task.id}" data-id="${task.id}" data-url="${task.task_url}" class="d-flex w-100 mb-4"></li>`);
    li.html(`
        <div>
            <div class="marker priority priority-${task.priority} ${taskCompleted}"><i class="fa-solid fa-circle"></i></div>
            <div class="marker checkmark ${taskCompleted}"><i class="fa-solid fa-circle-check"></i></div>
        </div>
        <div class="d-flex flex-column w-100">
            <p class="mb-2 task-content ${taskCompleted}">${task.content.replace(/\n/g, "<br>\n")}</p>
            <div class="d-flex">
                <div class="${taskCompleted} ${task.due_date !== "" ? "due-date" : ""} task-date justify-content-center align-items-center rounded-5">${task.due_date}</div>
            </div>
        </div>
    `);

    // Add interface buttons and events
    const divButtons = $(`<div class="text-center"></div>`);
    li.append(divButtons);
    
    // Edit button
    const editButton = $(`<button type="button" class="${taskCompleted} task-icon task-edit"><i class="fa-solid fa-pen-to-square"></i></button>`);
    divButtons.append(editButton);
    
    // Delete button
    const deleteButton = $(`<button type="button" class="task-icon task-delete"><i class="fa-solid fa-trash-can"></i></button>`);
    divButtons.append(deleteButton);

    return li;
}

/**
 * Update the task li with new task data.
 * @param {json} jsonData the json task data
 */
function updateTask(jsonData)
{
    $(`#t-${jsonData.id}`).replaceWith(createTask(jsonData));
}

/**
 * Adjust the count indicator by passing the value to increase or decrease by.
 * @param {int} value the value to increase or decrease by
 */
function updateActiveTaskCount(addValue)
{
    const newValue = parseInt($("#task-active-count span").text()) + addValue;
    $("#task-active-count span").text(newValue);
}
