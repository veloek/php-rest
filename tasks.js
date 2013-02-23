var BASE_PATH = "http://dev.vtek.no/php-rest";

// On load
$(function() {

	// Login check
	$.ajax({
		type: "GET",
		url: BASE_PATH + "/auth",
		xhrFields: {
			withCredentials: true
		},
		success: function() {
			$("#login").toggle();
			$("#logout").toggle();
		},
		error: function(result) {
			if (result.status == 401) {
				$("#username").focus();
			} else {
				$("#message").text("Error while contacting web service");
			}
		}
	});

	// Get all tasks
	$.ajax({
		type: "GET",
		url: BASE_PATH + "/tasks",
		xhrFields: {
			withCredentials: true
		},
		success: function(result) {
			$.each(result.tasks, function(i, task) {
				addTaskToList(task);
			});
		},
		error: function(result) {
			if (result.status == 404) {
				$("#message").text("No tasks in list. Add some!");
			} else {
				$("#message").text("Error while contacting web service");
			}
		}
	});

	$("#login").on("submit", function(evt) {
		evt.preventDefault();

		$.ajax({
			type: "POST",
			url: BASE_PATH + "/auth",
			xhrFields: {
				withCredentials: true
			},
			contentType: "application/json",
			data: JSON.stringify({
				username: $("#username").val(),
				password: $("#password").val()
			}),
			success: function(result) {
				$("#login").toggle("slow");
				$("#logout").toggle("slow");
			},
			error: function(result) {
				if (result.status == 401) {
					$("#message").text("Wrong username and/or password. Try john/doe or root/god");
				} else {
					$("#message").text("Error while authenticating. Try again later.");
				}
			}
		});
	});
	
	$("#logout").on("submit", function(evt) {
		evt.preventDefault();

		$.ajax({
			type: "DELETE",
			url: BASE_PATH + "/auth",
			xhrFields: {
				withCredentials: true
			},
			success: function(result) {
				$("#login").toggle("slow");
				$("#logout").toggle("slow");
			},
			error: function(result) {
				$("#message").text("Error while loggin out. Try again later.");
			}
		});
	});
	
	$("#new_task").on("submit", function(evt) {
		evt.preventDefault();

		$.ajax({
			type: "POST",
			url: BASE_PATH + "/tasks",
			xhrFields: {
				withCredentials: true
			},
			contentType: "application/json",
			data: JSON.stringify({
				task: $("#task_input").val()
			}),
			success: function(result) {
				addTaskToList(result);
			},
			error: function(result) {
				if (result.status == 401) {
					$("#message").text("You must login to create tasks");
				} else {
					$("#message").text("Error while saving task. Try again later");
				}
			}
		});
	});
	
	$("#tasks_container").on("click", ".del_task", function() {
		var taskId = $(this).closest(".task").attr("id").substring(4);
		var that = this;
		$.ajax({
			type: "DELETE",
			url: BASE_PATH + "/tasks/" + taskId,
			xhrFields: {
				withCredentials: true
			},
			success: function(result) {
				$(that).toggle("slow").remove();
			},
			error: function(result) {
				if (result.status == 401) {
					$("#message").text("You must be logged in to delete tasks");
				} else if (result.status == 405) {
					$("#message").text("You must be logged in as root to delete tasks");
				} else {
					$("#message").text("Error while deleting task. Try again later");
				}
			}
		});
	});
});

function addTaskToList(task) {
	var html =
	$("<div id=\"tId_"+task.id+"\" class=\"task\">" +
		"<h1>"+task.content+"</h1>" +
		"<h2>Created by "+task.createdBy+"</h2>" +
		"<div class=\"del_task\"></div>" +
	"</div>");

	html.hide();

	$("#tasks_container").prepend(html);

	html.toggle("slow");
}
