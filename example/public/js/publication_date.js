IMask(document.querySelector('input[name="publication_date"]'),{mask:Date,pattern:"Y-m-d",blocks:{d:{mask:IMask.MaskedRange,from:1,to:31,maxLength:2},m:{mask:IMask.MaskedRange,from:1,to:12,maxLength:2},Y:{mask:IMask.MaskedRange,from:1e3,to:9999}},format:function(a){var e=a.getDate(),t=a.getMonth()+1;return e<10&&(e="0"+e),t<10&&(t="0"+t),[a.getFullYear(),t,e].join("-")},parse:function(a){var e=a.split("-");return new Date(e[0],e[1]-1,e[2])},autofix:!0,lazy:!0,overwrite:!0});