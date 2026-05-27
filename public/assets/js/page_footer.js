

let dialogoProcesando;

/**
 * Muestra un cuadro de diálogo indicando que el sistema está procesando.
 */
function mostrarDialogoProcesando() {
	dialogoProcesando = $.dialog({
		title: 'Procesando...',
		icon: 'fas fa-spinner fa-spin',
		content: 'Por favor espera un momento',
		closeIcon: false,
		backgroundDismiss: false,
		draggable: false,
		useBootstrap: true,
		type: 'blue',
		theme: 'modern',
	});
}

/**
 * Cierra el cuadro de diálogo de procesamiento si está abierto.
 */
function cerrarDialogoProcesando() {
	if (dialogoProcesando) {
		dialogoProcesando.close();
		dialogoProcesando = null;
	}
}

// Configuraciones del jQuery Validator
$.validator.setDefaults({
	ignore: 'input[type=hidden], .select2-search__field',
	errorClass: 'is-invalid',
	validClass: 'is-valid',
	highlight: function(element) {
		$(element).addClass('is-invalid').removeClass('is-valid');
	},
	unhighlight: function(element) {
		$(element).removeClass('is-invalid').addClass('is-valid');
		// Eliminar mensaje de error al corregir el campo
		$(element).parent().find('.invalid-feedback').remove();
	},
	errorPlacement: function(error, element) {
		// Eliminar mensajes de error previos para evitar duplicados
		$(element).parent().find('.invalid-feedback').remove();
		
		const feedbackContainer = $(element).closest('.form-floating').find('.invalid-feedback');

		if (feedbackContainer.length) {
			feedbackContainer.text(error.text()).show();
		} else {
			const newFeedback = $('<div class="invalid-feedback fw-bold"></div>').text(error.text());
			$(element).parent().append(newFeedback);
		}
	},
	submitHandler: function (form) {
		mostrarDialogoProcesando();
		form.submit();
	}
});

// Inicialización de plugins
$('.select').select2();
$('.validarForm').validate();

// Cerrar diálogo al navegar hacia atrás
window.addEventListener('pageshow', function () {
	cerrarDialogoProcesando();
});

// Delegación de eventos para confirmaciones genéricas
$(document).on('click', '[data-confirm]', function(e) {
	e.preventDefault();
	
	const button = $(this);
	const form = button.closest('form');
	const url = form.length ? form.attr('action') : button.data('url');
	const method = button.data('method') || (form.length ? (form.find('input[name="_method"]').val() || 'POST') : 'POST');
	const msg = button.data('msg') || '¿Estás seguro?';
	const action = button.data('action') || 'realizar acción';
	const title = button.data('title') || 'Confirmar ' + action;

	if (!url) {
		console.error('data-confirm: No URL found');
		return;
	}

	let dialogConfig = {
		title: title,
		content: msg,
		theme: 'modern',
		typeAnimated: true,
		buttons: {
			confirm: {
				text: 'Confirmar',
				btnClass: 'btn-primary',
				action: function () {
					if (form.length) {
						mostrarDialogoProcesando();
						form.submit();
						return;
					}

					const newForm = document.createElement('form');
					newForm.method = 'POST';
					newForm.action = url;

					const csrfInput = document.createElement('input');
					csrfInput.type = 'hidden';
					csrfInput.name = '_token';
					csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
					newForm.appendChild(csrfInput);

					if (method.toUpperCase() !== 'POST') {
						const methodInput = document.createElement('input');
						methodInput.type = 'hidden';
						methodInput.name = '_method';
						methodInput.value = method.toUpperCase();
						newForm.appendChild(methodInput);
					}

					mostrarDialogoProcesando();
					document.body.appendChild(newForm);
					newForm.submit();
				}
			},
			cancel: {
				text: 'Cancelar',
				btnClass: 'btn-secondary'
			}
		}
	};

	if (action === 'reactivar') {
		dialogConfig.type = 'green';
		dialogConfig.icon = 'fa fa-check-circle fa-2x';
		dialogConfig.buttons.confirm.text = 'Sí, reactivar';
	} else if (action === 'desactivar' || action === 'eliminar') {
		dialogConfig.type = 'red';
		dialogConfig.icon = 'fa fa-trash fa-2x';
		dialogConfig.buttons.confirm.text = action === 'eliminar' ? 'Sí, eliminar' : 'Sí, desactivar';
	}

	$.confirm(dialogConfig);
});