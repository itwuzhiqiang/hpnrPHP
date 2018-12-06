module.exports = class {

	onDataLoad() {
		return {
			handleTemplateGet: this.fetchQL('handleTemplateGet'),
		};
	}

	goBack() {
		padapp.navigate.pop();
	}

	goChooseTemplate(item) {
		padapp.app.emit('/handle.responsibility.template', {
			data: item,
		});
		padapp.navigate.pop()
	}

}