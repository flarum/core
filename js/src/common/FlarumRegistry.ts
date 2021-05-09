interface ExportRegistry {
  moduleExports: object;

  onLoads: object;

  /**
   * Add an instance to the registry.
   * This serves as the equivalent of `flarum.core.compat[id] = object`
   */
  add(namespace: string, id: string, object: any): void;

  /**
   * Add a function to run when object of id "id" is added (or overriden).
   * If such an object is already registered, the handler will be applied immediately.
   */
  onLoad(namespace: string, id: string, handler: Function): void;

  /**
   * Retrieve an object of type `id` from the registry.
   */
  get(namespace: string, id: string): any;
}

export default class FlarumRegistry implements ExportRegistry {
  moduleExports = new Map<string, any>();
  onLoads = new Map<string, Function[]>();

  protected genKey(namespace: string, id: string): string {
    return `${namespace};${id}`;
  }

  add(namespace: string, id: string, object: any) {
    const key = this.genKey(namespace, id);

    const onLoads = this.onLoads.get(key);
    if (onLoads) {
      onLoads.reduce((acc, handler) => handler(acc), object);
    }

    this.moduleExports.set(key, object);
  }

  onLoad(namespace: string, id: string, handler: Function) {
    const key = this.genKey(namespace, id);

    const loadedObject = this.moduleExports.get(key);
    if (loadedObject) {
      this.moduleExports[id] = handler(loadedObject);
    } else {
      const currOnLoads = this.onLoads.get(key);
      this.onLoads.set(key, [...(currOnLoads || []), handler]);
    }
  }

  get(namespace: string, id: string): any {
    const key = this.genKey(namespace, id);

    return this.moduleExports.get(key);
  }
}
