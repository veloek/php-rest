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
			if (result.tasks.length == 0) {
				$("#message").text("No tasks in list. Add some!");
			} else {
				$.each(result.tasks, function(i, task) {
					addTaskToList(task);
				});
			}
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
				$("#message").text("");
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
				$("#message").text("");
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
				$("#message").text("");
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
		var task = $(this).closest(".task");
		var taskId = task.attr("id").substring(4);
		var that = this;
		$.ajax({
			type: "DELETE",
			url: BASE_PATH + "/tasks/" + taskId,
			xhrFields: {
				withCredentials: true
			},
			success: function(result) {
				task.toggle("slow", function() {
					task.remove();

					if ($("#tasks_container").children().length == 0) {
						$("#message").text("No tasks in list. Add some!");
					}
				});
				$("#message").text("");
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

	$("#tasks_container").on("click", ".task_title", function() {
		var html = $("<form class=\"change_title_form\"><input type=\"text\" class=\"change_title\" value=\""+$(this).text()+"\"/></form>");
		$(this).hide().after(html);
		html.children(".change_title").focus();
	});

	$("#tasks_container").on("submit", ".change_title_form", function(evt) {
		evt.preventDefault();

		var task = $(this).closest(".task");
		var taskId = task.attr("id").substring(4);
		var input = $(this).children(".change_title");
		var that = this;
		$.ajax({
			type: "PUT",
			url: BASE_PATH + "/tasks/" + taskId,
			contentType: "application/json",
			xhrFields: {
				withCredentials: true
			},
			data: JSON.stringify({
				task: $(input).val()
			}),
			success: function(result) {
				task.children(".task_title").text(result.content);
				$("#message").text("");
			},
			error: function(result) {
				if (result.status == 401) {
					$("#message").text("You must be logged in to edit tasks");
				} else {
					$("#message").text("Error while saving task. Try again later");
				}
			},
			complete: function() {
				task.children(".task_title").show();
				$(that).remove();
			}
		});
	});

	$("#tasks_container").on("focusout", ".change_title", function() {
		$(this).parent().submit();
	});
});

function addTaskToList(task) {
	var html =
	$("<div id=\"tId_"+task.id+"\" class=\"task\">" +
		"<h1 class=\"task_title\">"+task.content+"</h1>" +
		"<h2>Created by "+task.createdBy+"</h2>" +
		"<div class=\"del_task\"></div>" +
	"</div>");

	html.hide();

	$("#tasks_container").prepend(html);

	html.toggle("slow");
}
