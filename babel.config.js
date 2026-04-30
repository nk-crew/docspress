module.exports = {
  presets: ['@babel/preset-env'],
  plugins: [
    [
      '@babel/plugin-transform-react-jsx',
      {
        pragma: 'wp.element.createElement',
      },
    ],
    ['@babel/plugin-transform-object-rest-spread'],
  ],
};
