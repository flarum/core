export default class RequestError {
  status: string | number;
  options: object;
  xhr: XMLHttpRequest;

  responseText: string | null;
  response: any | null;

  alert: any;

  constructor(status: string | number, responseText: string | null, options: object, xhr: XMLHttpRequest) {
    this.status = status;
    this.responseText = responseText;
    this.options = options;
    this.xhr = xhr;

    try {
      this.response = JSON.parse(responseText!);
    } catch (e) {
      this.response = null;
    }

    this.alert = null;
  }
}
