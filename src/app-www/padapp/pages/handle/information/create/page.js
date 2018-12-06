module.exports = class {

    onDataLoad(props) {
        return {
            dataServer: padapp.app.getConfig('rpapi'),
            policeInfo: this.fetchQL('policeInfo'),
            handleSaveParty: this.dataQL('handleSaveParty'),//录入当事人
            handleRemoveParty: this.dataQL('handleRemoveParty'),//移除当事人
            handleDriverInfo: this.dataQL('handleDriverInfo'),//根据身份证获取信息
            handleCarInfo: this.dataQL('handleCarInfo'),//根据车牌获取信息
            rpaId: props.rpaId,
            apiOrder: props.number,
            data: props.data ? props.data : '',
            show: false,
            check: false,
            img: '',
            img1: '',
            apiPtText: '',
            name: '',
            phone: '',
            card: '',
            number: '',
            apiIc: '',
            apiIcName: '',
            checkName: 0,
            vehicle_typeStr: '',
            ljjf: '',
            zjzt: '',
            vehicleStatus: '',
            delete: false,
        };
    }

    onLoad() {
        this.addListener('/handle.information.list', (data) => {
            this.setState({
                apiPtText: data.data.instructions,
                apiPt: data.data.ptid,
            })
        });
        this.addListener('/handle.queryAll.list', (data) => {
            this.setState({
                apiIc: data.data.insuranceId,
                apiIcName: data.data.insuranceName,
            })
        });
    }

    onLoaded() {
        $(':text').on('focus', function () {
            var __this = this;
            setTimeout(function () {
                __this.scrollIntoViewIfNeeded();
            }, 200)
        });
        let data = this.state.data;
        console.log(data);
        if (data) {
            this.setState({
                apiPtText: data.aviPlateTypeText,
                name: data.apPartiesName,
                phone: data.apPartiesPhone,
                card: data.apLicenseNo,
                number: data.aviPlateNumber,
                apiPt: data.aviPlateType,
                apId: data.apId,
                aviId: data.aviId,
                img: data.acLicensePhotos,
                img1: data.aviLicensePhotos,
                apiIc: data.aviInsuranceCompany,
                apiIcName: data.aviInsuranceCompanyName,
                delete: true,
            }, () => {
                console.log(this.state.apiOrder)
            })
        }
    }

    isCardID(sId) {
        let aCity = {
            11: "北京",
            12: "天津",
            13: "河北",
            14: "山西",
            15: "内蒙古",
            21: "辽宁",
            22: "吉林",
            23: "黑龙江",
            31: "上海",
            32: "江苏",
            33: "浙江",
            34: "安徽",
            35: "福建",
            36: "江西",
            37: "山东",
            41: "河南",
            42: "湖北",
            43: "湖南",
            44: "广东",
            45: "广西",
            46: "海南",
            50: "重庆",
            51: "四川",
            52: "贵州",
            53: "云南",
            54: "西藏",
            61: "陕西",
            62: "甘肃",
            63: "青海",
            64: "宁夏",
            65: "新疆",
            71: "台湾",
            81: "香港",
            82: "澳门",
            91: "国外"
        };
        let iSum = 0;
        if (!/^\d{17}(\d|x)$/i.test(sId)) {
            this.overlay.alert('你输入的身份证长度或格式错误');
            return false;
        }
        sId = sId.replace(/x$/i, "a");
        if (aCity[parseInt(sId.substr(0, 2))] == null) {
            this.overlay.alert('你的身份证地区非法');
            return false;
        }
        let sBirthday = sId.substr(6, 4) + "-" + Number(sId.substr(10, 2)) + "-" + Number(sId.substr(12, 2));
        let d = new Date(sBirthday.replace(/-/g, "/"));
        if (sBirthday != (d.getFullYear() + "-" + (d.getMonth() + 1) + "-" + d.getDate())) {
            this.overlay.alert('身份证上的出生日期非法');
            return false;
        }
        for (var i = 17; i >= 0; i--) {
            iSum += (Math.pow(2, i) % 11) * parseInt(sId.charAt(17 - i), 11);
        }
        if (iSum % 11 != 1) {
            this.overlay.alert('你输入的身份证号非法');
            return false;
        }
        return true;
    }

    setVal(e, type) {
        let val = e.target.value;
        if (type === 'name') {
            this.setState({
                name: val,
            })
        }
        if (type === 'phone') {
            this.setState({
                phone: val,
            })
        }
        if (type === 'number') {
            this.setState({
                number: val,
            })
        }
        if (type === 'card') {
            this.setState({
                card: val,
            });
        }
    }

    goList(type) {
        if (type == 1) {
            padapp.navigate.push({
                url: '/handle/information/list',
            });
        } else {
            padapp.navigate.push({
                url: '/handle/information/list1',
            });
        }

    }

    goCheck() {
        let check = this.state.check;
        let rpaId = this.state.rpaId;
        let apiLp = this.state.img;
        let apiVlp = this.state.img1;
        let apiName = this.state.name;
        let apiNo = this.state.card;
        // let apiFn = this.state.apiFn;
        let apiPhone = this.state.phone;
        let apiPn = this.state.number;
        // let apiVas = this.state.apiVas;
        let apiPt = this.state.apiPt;
        let apiPtText = this.state.apiPtText;
        let apiOrder = this.state.apiOrder;
        let apiIc = this.state.apiIc;
        let apiIcName = this.state.apiIcName;

        let reg = /^[\u4e00-\u9fa5]{1}[A-Z]{1}(?:(?![a-zA-Z]{5})[0-9a-zA-z]){5}$/
        if (!check) {
            if (!apiLp) {
                this.overlay.msg('请上传身份证或行驶证照片');
                return;
            }
            if (!apiName) {
                this.overlay.msg('请输入当事人姓名');
                return;
            }
            if (!this.isCardID(apiNo)) {
                this.overlay.msg('请输入正确的当事人身份证号码');
                return;
            }
            if (!(/^1\d{10}$/.test(apiPhone))) {
                this.overlay.msg('请输入正确的当事人手机号');
                return;
            }
            if (!apiVlp) {
                this.overlay.msg('请上传行驶证照片');
                return;
            }

            if (!apiPn && !reg.test(apiPn)) {
                this.overlay.msg('请输入正确的车牌号');
                return;
            }
            if (!apiPt) {
                this.overlay.msg('请选择号牌种类');
                return;
            }
            // if (!apiIc) {
            // 	this.overlay.msg('请选择保险公司');
            // 	return;
            // }
            let judge = 1;
            let judge1 = 1;
            if (apiNo) {
                this.state.handleDriverInfo.setParam('party_idnum', apiNo).fetch((json) => {
                    if (json) {
                        if (json.party_name) {
                            if (json.party_name == apiName) {
                                this.setState({
                                    checkName: 2,
                                });
                                if (apiPn) {
                                    this.state.handleCarInfo.setParam('vlp_no', apiPn).fetch((data) => {
                                        if (data) {
                                            this.setState({
                                                zjzt: data.zjzt,
                                                show: true,
                                                check: true,
                                                vehicle_typeStr: json.vehicle_typeStr ? json.vehicle_typeStr : json.vehicle_type,
                                                ljjf: json.ljjf ? json.ljjf : 0,
                                                vehicleStatus: json.status,
                                            })
                                        } else {
                                            judge1 = 0;
                                        }
                                    });
                                }
                            } else {
                                judge = 0;
                                this.setState({
                                    checkName: 1,
                                });
                                this.overlay.msg('输入姓名与身份证姓名不匹配');
                            }
                        }
                    }
                });
            }

        } else {
            this.state.handleSaveParty
                .setParam('rpaId', rpaId)
                .setParam('apiLp', apiLp)
                .setParam('apiVlp', apiVlp)
                .setParam('apiName', apiName)
                .setParam('apiPhone', apiPhone)
                .setParam('apiPn', apiPn)
                .setParam('apiPt', apiPt)
                .setParam('apiNo', apiNo)
                .setParam('apiPtText', apiPtText)
                .setParam('apiOrder', apiOrder)
                .setParam('apiIc', apiIc)
                .setParam('apiIcName', apiIcName)
                .fetch((json) => {
                    if (json) {
                        padapp.app.emit('/handle.information.create', {});
                        this.setState({
                            show: true,
                            check: true,
                        });
                        padapp.navigate.pop();
                    }
                })

        }


    }

    goDelete() {
        let del = this.state.delete;
        if (del) {
            let rpaId = this.state.rpaId;
            let apId = this.state.apId;
            let aviId = this.state.aviId;

            this.state.handleRemoveParty
                .setParam('rpaId', rpaId)
                .setParam('apId', apId)
                .setParam('aviId', aviId)
                .fetch((json) => {
                    padapp.app.emit('/handle.information.create', {});
                    padapp.navigate.pop()
                })
        }
    }

    goCancel() {
        this.setState({
            show: false,
        })
    }

    goSure() {
        padapp.navigate.pop()
    }

    goBack() {
        padapp.navigate.pop()
    }

    /*
     三个参数
     file：一个是文件(类型是图片格式)，
     w：一个是文件压缩的后宽度，宽度越小，字节越小
     objDiv：一个是容器或者回调函数
     photoCompress()
     */

    canvasDataURL(path, obj, callback) {
        var img = new Image();
        img.src = path;
        img.onload = function () {
            var that = this;
            // 默认按比例压缩
            var w = that.width,
                h = that.height,
                scale = w / h;
            w = obj.width || w;
            h = obj.height || (w / scale);
            var quality = 0.7;  // 默认图片质量为0.7
            //生成canvas
            var canvas = document.createElement('canvas');
            var ctx = canvas.getContext('2d');
            // 创建属性节点
            var anw = document.createAttribute("width");
            anw.nodeValue = w;
            var anh = document.createAttribute("height");
            anh.nodeValue = h;
            canvas.setAttributeNode(anw);
            canvas.setAttributeNode(anh);
            ctx.drawImage(that, 0, 0, w, h);
            // 图像质量
            if (obj.quality && obj.quality <= 1 && obj.quality > 0) {
                quality = obj.quality;
            }
            // quality值越小，所绘制出的图像越模糊
            var base64 = canvas.toDataURL('image/jpeg', quality);
            // 回调函数返回base64的值
            callback(base64);
        }
    }

    photoCompress(file, w, objDiv) {
        let that = this;
        var ready = new FileReader();
        /*开始读取指定的Blob对象或File对象中的内容.
         当读取操作完成时,readyState属性的值会成为DONE,如果设置了onloadend事件处理程序,则调用之.
         同时,result属性中将包含一个data: URL格式的字符串以表示所读取文件的内容.*/
        ready.readAsDataURL(file);
        ready.onload = function () {
            var re = this.result;
            that.canvasDataURL(re, w, objDiv)
        }
    }

    /**
     * 将以base64的图片url数据转换为Blob
     * @param urlData
     * 用url方式表示的base64图片数据
     */
    convertBase64UrlToBlob(urlData) {
        var arr = urlData.split(','), mime = arr[0].match(/:(.*?);/)[1],
            bstr = atob(arr[1]), n = bstr.length, u8arr = new Uint8Array(n);
        while (n--) {
            u8arr[n] = bstr.charCodeAt(n);
        }
        return new Blob([u8arr], {type: mime});
    }

    uploadFile(type) {
        let that = this;
        let fileObj = '';
        let msg = '';
        let url = this.state.dataServer + '/accident/v1/app/file/uplaod?accountToken=' + this.state.policeInfo.data.sign; // 接收上传文件的后台地址

        var form = new FormData(); // FormData 对象

        if (type === 1) {
            fileObj = this.page.refs.file.files[0];
            msg = '身份证或驾驶证';
        } else {
            fileObj = this.page.refs.file1.files[0];
            msg = '行驶证';
        }

        if (typeof(fileObj) == 'object') {
            let load = that.overlay.shadeLoading();
            if (fileObj.size / 1024 > 1025) { //大于1M，进行压缩上传
                this.photoCompress(fileObj, {
                    quality: 0.2
                }, function (base64Codes) {
                    var bl = that.convertBase64UrlToBlob(base64Codes);
                    form.append("file", bl, "file_" + Date.parse(new Date()) + ".jpg"); // 文件对象
                    $.ajax({
                        url: url,
                        type: 'POST',
                        cache: false,
                        data: form,
                        processData: false,
                        contentType: false
                    }).done((res) => {
                        that.overlay.msg(msg + '上传成功');
                        that.overlay.loadingClose(load);
                        if (res.data) {
                            if (type == 1) {
                                that.setState({
                                    img: res.data,
                                })
                            } else {
                                that.setState({
                                    img1: res.data,
                                })
                            }
                        }
                    }).fail(function (res) {
                        that.overlay.msg('上传失败');
                        that.overlay.loadingClose(load);
                    });
                });
            } else { //小于等于1M 原图上传
                form.append("file", fileObj); // 文件对象
                $.ajax({
                    url: url,
                    type: 'POST',
                    cache: false,
                    data: form,
                    processData: false,
                    contentType: false
                }).done((res) => {
                    that.overlay.msg(msg + '上传成功');
                    that.overlay.loadingClose(load);
                    if (res.data) {
                        if (type == 1) {
                            that.setState({
                                img: res.data,
                            })
                        } else {
                            that.setState({
                                img1: res.data,
                            })
                        }
                    }
                }).fail(function (res) {
                    that.overlay.msg('上传失败');
                    that.overlay.loadingClose(load);
                });
            }
        }
    }

    // uploadFile(type) {
    // 	let formData = new FormData();
    // 	let files = '';
    // 	if (type === 1) {
    // 		files = this.page.refs.file.files;
    // 	} else {
    // 		files = this.page.refs.file1.files;
    // 	}
    // 	for (let k = 0; k < files.length; k++) {
    // 		formData.append('file', files[k]);
    // 	}
    // 	$.ajax({
    // 		url: this.state.dataServer + '/accident/v1/app/file/uplaod?accountToken=' + this.state.policeInfo.data.sign,
    // 		type: 'POST',
    // 		cache: false,
    // 		crossDomain: true,
    // 		data: formData,
    // 		processData: false,
    // 		contentType: false,
    // 	}).done((res) => {
    // 		this.overlay.msg(res.msg);
    // 		if (res.data) {
    // 			if (type == 1) {
    // 				this.setState({
    // 					img: res.data,
    // 				})
    // 			} else {
    // 				this.setState({
    // 					img1: res.data,
    // 				})
    // 			}
    // 		}
    // 	}).fail(function (res) {
    // 		console.log(res)
    // 	});
    // }

}