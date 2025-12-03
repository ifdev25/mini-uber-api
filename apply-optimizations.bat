@echo off
echo ========================================
echo   OPTIMISATION DES PERFORMANCES
echo ========================================
echo.

echo [1/5] Arret des conteneurs...
docker compose down

echo.
echo [2/5] Rebuild des images...
docker compose build --no-cache

echo.
echo [3/5] Demarrage des conteneurs...
docker compose up -d

echo.
echo [4/5] Installation des dependances...
docker compose exec php composer install --optimize-autoloader

echo.
echo [5/5] Vidage du cache Symfony...
docker compose exec php php bin/console cache:clear

echo.
echo ========================================
echo   OPTIMISATION TERMINEE !
echo ========================================
echo.
echo Testez maintenant les performances :
echo   POST /api/login devrait etre ^< 500ms
echo.
pause
