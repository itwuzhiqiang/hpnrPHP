module.exports = class {

	onDataLoad(props) {
		return {
			url: padapp.app.getConfig('server') + '/index.pdf.phtml?pdfUrl=' + props.pdfUrl,
		};
	}
};