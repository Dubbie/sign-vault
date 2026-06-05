ARG BASE_IMAGE
FROM $BASE_IMAGE

RUN . /etc/os-release \
  && mkdir -p /usr/share/keyrings \
  && curl -fsSL https://packages.sury.org/php/apt.gpg \
    | gpg --batch --yes --dearmor -o /usr/share/keyrings/deb.sury.org-php.gpg \
  && echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ ${VERSION_CODENAME} main" \
    > /etc/apt/sources.list.d/php.list \
  && apt-get update \
  && apt-get install -y ffmpeg \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*
