((Drupal, drupalSettings, once) => {
  Drupal.behaviors.ginLoginWallpaper = {
    attach: function(context) {
      once("ginLogin", ".user-form-page__wallpaper", context).forEach((() => {
        Drupal.ginLoginWallpaper.randomWallpaper();
      }));
    }
  }, Drupal.ginLoginWallpaper = {
    randomWallpaper: () => {
      const path = drupalSettings.gin_login.path + "/images/wallpapers/", wallpapers = [ "pexels-kehn-hermano-5962572.jpg", "pexels-photo-14382403.jpeg", "pexels-pixabay-208745.jpg", "pexels-pixabay-258447.jpg", "pexels-zetong-li-18414754.jpg", "pexels-quintin-gellar-612949.jpg", "pexels-photo-6039188.jpeg" ], wallpaper = wallpapers[Math.floor(Math.random() * wallpapers.length)];
      let image = new Image;
      image.src = path + wallpaper, document.querySelector(".gin-login .user-form-page__wallpaper").appendChild(image);
    }
  };
})(Drupal, drupalSettings, once);