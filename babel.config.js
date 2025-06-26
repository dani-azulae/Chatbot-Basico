module.exports = {
  presets: [
    ['@babel/preset-env', {
      useBuiltIns: 'usage',
      corejs: 3,
      targets: {
        browsers: ["> 1%", "last 2 versions", "not ie <= 11"]
      }
    }]
  ],  plugins: [
    '@babel/plugin-transform-runtime',
    '@babel/plugin-transform-object-rest-spread',
    '@babel/plugin-syntax-dynamic-import'
  ]
};
