// 认证相关工具函数
const auth = {
    setToken(token) {
        localStorage.setItem('tcm_admin_token', token);
    },
    getToken() {
        return localStorage.getItem('tcm_admin_token');
    },
    removeToken() {
        localStorage.removeItem('tcm_admin_token');
    },
    setUserInfo(user) {
        localStorage.setItem('tcm_admin_user', JSON.stringify(user));
    },
    getUserInfo() {
        const data = localStorage.getItem('tcm_admin_user');
        return JSON.parse(data || 'null');
    },
    clear() {
        this.removeToken();
        this.removeUserInfo();
    },
    clearAll() {
        this.removeToken();
        this.removeUserInfo();
    }
};

// API请求封装
const api = {
    // 发送HTTP请求
    async request(url, options = {}) {
        const fullUrl = API_BASE_URL + url;
        const token = auth.getToken();
        
        const headers = {
            'Content-Type': 'application/json',
            ...options.headers
        };
        
        if (token) {
            headers['Authorization'] = 'Bearer ' + token;
        }
        
        const response = await fetch(fullUrl, {
            ...options,
            headers
        });
        
        // 处理响应
        if (response.status === 401) {
            auth.clearAll();
            window.location.href = '/admin/login.html';
            return null;
        }
        
        if (response.status === 429) {
            layer.msg('请求过于频繁，请稍后再试', { icon: 2 });
            return null;
        }
        
        if (response.status === 500) {
            layer.msg('服务器错误，请稍后再试', { icon: 2 });
            return null;
        }
        
        const data = await response.json();
        
        if (data.code !== 200 && data.code !== 0) {
            layer.msg(data.message || '操作失败', { icon: 2 });
            return null;
        }
        
        return data;
    },
    
    // GET请求
    async get(url, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const fullUrl = queryString ? `${url}?${queryString}` : url;
        
        return this.request(fullUrl, {
            method: 'GET'
        });
    },
    
    // POST请求
    async post(url, data = {}) {
        return this.request(url, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },
    
    // PUT请求
    async put(url, data = {}) {
        return this.request(url, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    },
    
    // DELETE请求
    async del(url) {
        return this.request(url, {
            method: 'DELETE'
        });
    }
};

// 工具函数
const utils = {
    // 时间格式化
    formatTime(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        const seconds = String(date.getSeconds()).padStart(2, '0');
        return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    },
    
    // 文件大小格式化
    formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },
    
    // 获取URL参数
    getQueryParam(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name);
    }
};

// 统一导出
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { auth, api, utils };
}
