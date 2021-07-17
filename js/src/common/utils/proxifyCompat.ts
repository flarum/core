export default (compat: { [key: string]: any }, namespace: string) => {
  // regex to replace common/ and NAMESPACE/ for core & core extensions
  // and remove .js, .ts and .tsx extensions
  // e.g. admin/utils/extract --> utils/extract
  // e.g. tags/common/utils/sortTags --> tags/utils/sortTags
  const regex = new RegExp(`^(.+\/)?(${namespace}|common)\/`);
  const fileExt = /\.(\.js|\.tsx?)$/;

  return new Proxy(compat, {
    get: (obj, prop: string) => obj[prop] || obj[prop.replace(regex, '$1').replace(fileExt, '')],
  });
};
